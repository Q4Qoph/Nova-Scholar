<?php

namespace App\Filament\School\Pages;

use App\Models\FeeAdjustment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeReceiptAllocation;
use App\Models\FeeReceiptAllocationReversal;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\SchoolRefund;
use App\Models\User;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\CompleteSchoolReceiptRefund;
use App\Services\Schools\CreateSchoolFeeSchedule;
use App\Services\Schools\PostFeeChargeBatch;
use App\Services\Schools\RecordSchoolReceipt;
use App\Services\Schools\RequestSchoolFeeCredit;
use App\Services\Schools\RequestSchoolReceiptRefund;
use App\Services\Schools\ReverseSchoolReceiptAllocation;
use App\Services\Schools\ReviewSchoolFeeCredit;
use App\Services\Schools\ReviewSchoolReceiptRefund;
use App\Support\CurrencyMinorUnitFormatter;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FeeOperations extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Fees';

    protected static ?string $title = 'Fee operations';

    protected string $view = 'filament.school.pages.fee-operations';

    public static function canAccess(): bool
    {
        $school = Filament::getTenant();

        return $school instanceof School
            && Gate::allows('viewAny', [FeeSchedule::class, $school]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createFeeSchedule')
                ->label('Create schedule')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('currency')->default('KES')->required()->length(3)->regex('/^[A-Z]{3}$/')->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state))),
                    TextInput::make('amount_minor')->label('Amount in minor units')->helperText('For example, KES 1,250.00 is 125000 minor units.')->numeric()->integer()->minValue(1)->maxValue(PHP_INT_MAX)->required(),
                    Select::make('term_id')
                        ->label('Term')
                        ->options(fn (): array => $this->getSchool()->terms()->where('terms.status', 'open')->orderByDesc('terms.starts_on')->pluck('terms.name', 'terms.id')->all())
                        ->searchable(),
                    Select::make('class_group_id')
                        ->label('Class')
                        ->options(fn (): array => $this->getSchool()->classGroups()->where('class_groups.status', 'active')->orderBy('class_groups.name')->pluck('class_groups.name', 'class_groups.id')->all())
                        ->searchable(),
                    DatePicker::make('starts_on'),
                    DatePicker::make('ends_on')->afterOrEqual('starts_on'),
                ])
                ->action(function (array $data, CreateSchoolFeeSchedule $createSchoolFeeSchedule): void {
                    $schedule = $createSchoolFeeSchedule->handle($this->actor(), $this->getSchool(), $data);

                    Notification::make()
                        ->success()
                        ->title('Fee schedule created')
                        ->body("{$schedule->name} is ready for preview.")
                        ->send();
                }),
            Action::make('previewFeeBatch')
                ->label('Preview charges')
                ->icon('heroicon-o-eye')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('fee_schedule_id')
                        ->label('Fee schedule')
                        ->options(fn (): array => $this->getSchool()->feeSchedules()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                    TextInput::make('batch_key')->required()->maxLength(100)->rule('alpha_dash'),
                ])
                ->action(function (array $data, PostFeeChargeBatch $postFeeChargeBatch): void {
                    $school = $this->getSchool();
                    $schedule = $school->feeSchedules()->findOrFail($data['fee_schedule_id']);
                    $batch = $postFeeChargeBatch->preview($this->actor(), $school, $schedule, $data['batch_key']);

                    Notification::make()
                        ->success()
                        ->title('Charge preview ready')
                        ->body("{$batch->eligible_count} active enrolments · ".CurrencyMinorUnitFormatter::format($batch->total_minor, $schedule->currency))
                        ->send();
                }),
            Action::make('postFeeBatch')
                ->label('Post previewed batch')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('fee_charge_batch_id')
                        ->label('Previewed batch')
                        ->options(fn (): array => $this->getPostableBatchOptions())
                        ->required()
                        ->searchable(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Post fee charges?')
                ->modalDescription('The selected preview will be rechecked against the current schedule and eligible learners before charges are written.')
                ->action(function (array $data, PostFeeChargeBatch $postFeeChargeBatch): void {
                    $school = $this->getSchool();
                    $batch = $school->feeChargeBatches()
                        ->whereKey($data['fee_charge_batch_id'])
                        ->where('status', 'draft')
                        ->whereNotNull('preview_hash')
                        ->with('feeSchedule')
                        ->firstOrFail();
                    $postedBatch = $postFeeChargeBatch->handle($this->actor(), $school, $batch->feeSchedule, $batch->batch_key);

                    Notification::make()
                        ->success()
                        ->title('Fee charges posted')
                        ->body("{$postedBatch->eligible_count} charges · ".CurrencyMinorUnitFormatter::format($postedBatch->total_minor, $postedBatch->feeSchedule->currency))
                        ->send();
                }),
            Action::make('recordSchoolReceipt')
                ->label('Record receipt')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('source')->options(['cash' => 'Cash', 'bank' => 'Bank transfer', 'mpesa' => 'M-Pesa'])->default('cash')->live()->required(),
                    TextInput::make('source_reference')
                        ->label('Bank/M-Pesa reference')
                        ->maxLength(120)
                        ->required(fn (Get $get): bool => in_array($get('source'), ['bank', 'mpesa'], true))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),
                    TextInput::make('currency')->default('KES')->required()->length(3)->regex('/^[A-Z]{3}$/')->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state))),
                    TextInput::make('amount_minor')->label('Amount in minor units')->numeric()->integer()->minValue(1)->maxValue(PHP_INT_MAX)->required(),
                    DatePicker::make('received_on')->default(today()->toDateString())->maxDate(today())->required(),
                    Textarea::make('verification_note')->label('Evidence note')->helperText('Record a short staff confirmation note. Do not enter private account or payment details.')->maxLength(500),
                    Hidden::make('submission_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->action(function (array $data, RecordSchoolReceipt $recordSchoolReceipt): void {
                    $receipt = $recordSchoolReceipt->handle($this->actor(), $this->getSchool(), [
                        ...$data,
                        'source_reference' => filled($data['source_reference'] ?? null) ? strtoupper(trim($data['source_reference'])) : null,
                        'verification_note' => filled($data['verification_note'] ?? null) ? trim($data['verification_note']) : null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Receipt recorded as manually confirmed')
                        ->body(CurrencyMinorUnitFormatter::format($receipt->amount_minor, $receipt->currency)." · {$receipt->source_reference}")
                        ->send();
                }),
            Action::make('allocateSchoolReceipt')
                ->label('Allocate receipt')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('school_receipt_id')
                        ->label('Receipt')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => $this->searchAvailableReceipts($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->getAvailableReceiptLabel((int) $value))
                        ->live()
                        ->required(),
                    Select::make('fee_charge_id')
                        ->label('Posted charge')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search, Get $get): array => $this->searchOutstandingCharges($search, $get))
                        ->getOptionLabelUsing(fn ($value, Get $get): ?string => $this->getOutstandingChargeLabel((int) $value, $get))
                        ->required(),
                    TextInput::make('amount_minor')->label('Amount in minor units')->numeric()->integer()->minValue(1)->maxValue(PHP_INT_MAX)->required(),
                    Hidden::make('allocation_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->action(function (array $data, AllocateSchoolReceipt $allocateSchoolReceipt): void {
                    $school = $this->getSchool();
                    $receipt = $school->receipts()->whereKey($data['school_receipt_id'])->firstOrFail();
                    $allocation = $allocateSchoolReceipt->handle(
                        $this->actor(),
                        $school,
                        (int) $data['school_receipt_id'],
                        (int) $data['fee_charge_id'],
                        (int) $data['amount_minor'],
                        $data['allocation_key'],
                    );

                    Notification::make()
                        ->success()
                        ->title('Receipt amount allocated')
                        ->body(CurrencyMinorUnitFormatter::format($allocation->amount_minor, $receipt->currency))
                        ->send();
                }),
            Action::make('reverseSchoolReceiptAllocation')
                ->label('Reverse receipt allocation')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => $this->canManageFees() && $this->getSchool()->feeReceiptAllocations()->whereDoesntHave('reversal')->exists())
                ->schema([
                    Select::make('fee_receipt_allocation_id')
                        ->label('Allocation')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => $this->searchReversibleAllocations($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->getReversibleAllocationLabel((int) $value))
                        ->required(),
                    Textarea::make('reason')
                        ->label('Reason')
                        ->helperText('This reverses the allocation only; it does not refund cash.')
                        ->maxLength(500)
                        ->required(),
                    Hidden::make('reversal_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Reverse receipt allocation?')
                ->modalDescription('The full amount will return to the receipt balance and the charge outstanding balance. No cash refund will be issued.')
                ->action(function (array $data, ReverseSchoolReceiptAllocation $reverseSchoolReceiptAllocation): void {
                    $reversal = $reverseSchoolReceiptAllocation->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['fee_receipt_allocation_id'],
                        $data['reason'],
                        $data['reversal_key'],
                    );

                    Notification::make()
                        ->success()
                        ->title('Receipt allocation reversed')
                        ->body(CurrencyMinorUnitFormatter::format($reversal->amount_minor, $reversal->allocation->receipt->currency).' returned to the available receipt balance. No cash refund was issued.')
                        ->send();
                }),
            Action::make('requestSchoolFeeCredit')
                ->label('Request charge credit')
                ->icon('heroicon-o-document-minus')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('fee_charge_id')
                        ->label('Posted charge')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => $this->searchCreditableCharges($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->getCreditableChargeLabel((int) $value))
                        ->required(),
                    TextInput::make('amount_minor')->label('Credit in minor units')->numeric()->integer()->minValue(1)->maxValue(PHP_INT_MAX)->required(),
                    Textarea::make('reason')->label('Reason')->maxLength(500)->required(),
                    Hidden::make('adjustment_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->action(function (array $data, RequestSchoolFeeCredit $requestSchoolFeeCredit): void {
                    $adjustment = $requestSchoolFeeCredit->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['fee_charge_id'],
                        [
                            'adjustment_key' => $data['adjustment_key'],
                            'amount_minor' => (int) $data['amount_minor'],
                            'reason' => trim($data['reason']),
                        ],
                    );

                    Notification::make()
                        ->success()
                        ->title('Credit request submitted')
                        ->body('A different school administrator must review it before the balance changes.')
                        ->send();
                }),
            Action::make('reviewSchoolFeeCredit')
                ->label('Review charge credit')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn (): bool => $this->canManageFees() && $this->getSchool()->feeAdjustments()
                    ->where('status', 'pending')
                    ->where('requested_by_user_id', '!=', $this->actor()->id)
                    ->exists())
                ->schema([
                    Select::make('fee_adjustment_id')
                        ->label('Pending request')
                        ->options(fn (): array => $this->getPendingCreditOptions())
                        ->searchable()
                        ->required(),
                    Select::make('decision')->options(['approve' => 'Approve credit', 'reject' => 'Reject request'])->required()->live(),
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->helperText('Required when rejecting. Keep notes free of private payment details.')
                        ->maxLength(500)
                        ->required(fn (Get $get): bool => $get('decision') === 'reject'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Review charge credit request')
                ->action(function (array $data, ReviewSchoolFeeCredit $reviewSchoolFeeCredit): void {
                    $adjustment = $reviewSchoolFeeCredit->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['fee_adjustment_id'],
                        $data['decision'],
                        $data['review_note'] ?? null,
                    );

                    Notification::make()
                        ->success()
                        ->title($adjustment->status === 'approved' ? 'Credit approved' : 'Credit request rejected')
                        ->body('The review has been recorded in the school audit history.')
                        ->send();
                }),
            Action::make('requestSchoolReceiptRefund')
                ->label('Request receipt refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => $this->canManageFees())
                ->schema([
                    Select::make('school_receipt_id')
                        ->label('Receipt with available funds')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => $this->searchRefundableReceipts($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->getRefundableReceiptLabel((int) $value))
                        ->required(),
                    TextInput::make('amount_minor')->label('Refund in minor units')->numeric()->integer()->minValue(1)->maxValue(PHP_INT_MAX)->required(),
                    Select::make('refund_method')->label('Manual payout method')->options(['cash' => 'Cash', 'bank' => 'Bank transfer', 'mpesa' => 'M-Pesa'])->required(),
                    Textarea::make('reason')->maxLength(500)->required(),
                    Hidden::make('refund_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Request a school fee refund')
                ->modalDescription('This creates a request only. A different school administrator must approve it before the payout can be recorded.')
                ->action(function (array $data, RequestSchoolReceiptRefund $requestSchoolReceiptRefund): void {
                    $refund = $requestSchoolReceiptRefund->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['school_receipt_id'],
                        [
                            'refund_key' => $data['refund_key'],
                            'amount_minor' => (int) $data['amount_minor'],
                            'refund_method' => $data['refund_method'],
                            'reason' => trim($data['reason']),
                        ],
                    );

                    Notification::make()
                        ->success()
                        ->title('Refund request submitted')
                        ->body(CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency).' · a different school administrator must review it.')
                        ->send();
                }),
            Action::make('reviewSchoolReceiptRefund')
                ->label('Review receipt refund')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn (): bool => $this->canManageFees() && $this->getSchool()->refunds()
                    ->where('status', 'pending')
                    ->where('requested_by_user_id', '!=', $this->actor()->id)
                    ->exists())
                ->schema([
                    Select::make('school_refund_id')
                        ->label('Pending request')
                        ->options(fn (): array => $this->getPendingRefundOptions())
                        ->searchable()
                        ->required(),
                    Select::make('decision')->options(['approve' => 'Approve refund', 'reject' => 'Reject request'])->required()->live(),
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->helperText('Required when rejecting. Keep notes free of private payment details.')
                        ->maxLength(500)
                        ->required(fn (Get $get): bool => $get('decision') === 'reject'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Review receipt refund request')
                ->action(function (array $data, ReviewSchoolReceiptRefund $reviewSchoolReceiptRefund): void {
                    $refund = $reviewSchoolReceiptRefund->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['school_refund_id'],
                        $data['decision'],
                        $data['review_note'] ?? null,
                    );

                    Notification::make()
                        ->success()
                        ->title($refund->status === 'approved' ? 'Refund approved' : 'Refund request rejected')
                        ->body($refund->status === 'approved'
                            ? CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency).' reserved for manual payout.'
                            : 'The receipt funds are available again.')
                        ->send();
                }),
            Action::make('completeSchoolReceiptRefund')
                ->label('Record refund payout')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => $this->canManageFees() && $this->getSchool()->refunds()->where('status', 'approved')->exists())
                ->schema([
                    Select::make('school_refund_id')
                        ->label('Approved refund')
                        ->options(fn (): array => $this->getApprovedRefundOptions())
                        ->searchable()
                        ->live()
                        ->required(),
                    TextInput::make('payout_reference')
                        ->label('Bank/M-Pesa payout reference (optional for cash)')
                        ->maxLength(120)
                        ->required(fn (Get $get): bool => $this->getApprovedRefundMethod((int) $get('school_refund_id')) !== 'cash')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),
                    Hidden::make('completion_key')->default(fn (): string => (string) Str::uuid())->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Record manual refund payout')
                ->modalDescription('Only record this after the school has actually paid the refund. This does not initiate a cash, bank, or M-Pesa transfer.')
                ->action(function (array $data, CompleteSchoolReceiptRefund $completeSchoolReceiptRefund): void {
                    $refund = $completeSchoolReceiptRefund->handle(
                        $this->actor(),
                        $this->getSchool(),
                        (int) $data['school_refund_id'],
                        $data['completion_key'],
                        filled($data['payout_reference'] ?? null) ? trim($data['payout_reference']) : null,
                    );

                    Notification::make()
                        ->success()
                        ->title('Refund payout recorded')
                        ->body(CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency).' · recorded by '.$this->actor()->name)
                        ->send();
                }),
        ];
    }

    public function mount(): void
    {
        $this->getSchool();
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);

        return $school;
    }

    /**
     * @return Collection<int, FeeSchedule>
     */
    public function getSchedules(): Collection
    {
        return $this->getSchool()->feeSchedules()
            ->with(['term', 'classGroup'])
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, FeeChargeBatch>
     */
    public function getBatches(): Collection
    {
        return $this->getSchool()->feeChargeBatches()
            ->with('feeSchedule')
            ->withCount('charges')
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, FeeCharge>
     */
    public function getRecentCharges(): Collection
    {
        return $this->getSchool()->feeCharges()
            ->with('enrolment.learnerProfile')
            ->withSum('receiptAllocations', 'amount_minor')
            ->withSum('receiptAllocationReversals as receipt_allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['adjustments as approved_credits_minor' => fn (Builder $query): Builder => $query->where('kind', 'credit')->where('status', 'approved')], 'amount_minor')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, FeeAdjustment>
     */
    public function getRecentAdjustments(): Collection
    {
        return $this->getSchool()->feeAdjustments()
            ->with(['charge.enrolment.learnerProfile', 'requestedBy:id,name', 'reviewedBy:id,name'])
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, SchoolReceipt>
     */
    public function getRecentReceipts(): Collection
    {
        return $this->getSchool()->receipts()
            ->with('verifiedBy:id,name')
            ->withSum('allocations', 'amount_minor')
            ->withSum('allocationReversals as allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['refunds as reserved_refunds_sum_amount_minor' => fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'paid'])], 'amount_minor')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, SchoolRefund>
     */
    public function getRecentRefunds(): Collection
    {
        return $this->getSchool()->refunds()
            ->with(['receipt', 'requestedBy:id,name', 'reviewedBy:id,name', 'completedBy:id,name'])
            ->latest('id')
            ->limit(30)
            ->get();
    }

    /**
     * @return Collection<int, FeeReceiptAllocationReversal>
     */
    public function getRecentAllocationReversals(): Collection
    {
        return $this->getSchool()->feeReceiptAllocationReversals()
            ->with(['allocation.receipt', 'allocation.charge.enrolment.learnerProfile', 'reversedBy:id,name'])
            ->latest('id')
            ->limit(20)
            ->get();
    }

    private function canManageFees(): bool
    {
        return Gate::allows('create', [FeeSchedule::class, $this->getSchool()]);
    }

    private function actor(): User
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }

    /**
     * @return array<int, string>
     */
    private function getPostableBatchOptions(): array
    {
        return $this->getSchool()->feeChargeBatches()
            ->where('status', 'draft')
            ->whereNotNull('preview_hash')
            ->with('feeSchedule')
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (FeeChargeBatch $batch): array => [
                $batch->id => "{$batch->feeSchedule->name} · {$batch->batch_key} · {$batch->eligible_count} learners · ".CurrencyMinorUnitFormatter::format($batch->total_minor, $batch->feeSchedule->currency),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function searchReversibleAllocations(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $search = '%'.trim($search).'%';

        return $this->getSchool()->feeReceiptAllocations()
            ->whereDoesntHave('reversal')
            ->where(function (Builder $query) use ($search): void {
                $query->whereHas('receipt', fn (Builder $receiptQuery): Builder => $receiptQuery->whereRaw('LOWER(source_reference) LIKE LOWER(?)', [$search]))
                    ->orWhereHas('charge.enrolment', function (Builder $enrolmentQuery) use ($search): void {
                        $enrolmentQuery->whereRaw('LOWER(admission_number) LIKE LOWER(?)', [$search])
                            ->orWhereHas('learnerProfile', function (Builder $profileQuery) use ($search): void {
                                $profileQuery->whereRaw('LOWER(first_name) LIKE LOWER(?)', [$search])
                                    ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', [$search])
                                    ->orWhereRaw('LOWER(preferred_name) LIKE LOWER(?)', [$search]);
                            });
                    });
            })
            ->with(['receipt', 'charge.enrolment.learnerProfile'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (FeeReceiptAllocation $allocation): array => [
                $allocation->id => $this->allocationLabel($allocation),
            ])
            ->all();
    }

    private function getReversibleAllocationLabel(int $allocationId): ?string
    {
        $allocation = $this->getSchool()->feeReceiptAllocations()
            ->whereDoesntHave('reversal')
            ->whereKey($allocationId)
            ->with(['receipt', 'charge.enrolment.learnerProfile'])
            ->first();

        return $allocation instanceof FeeReceiptAllocation ? $this->allocationLabel($allocation) : null;
    }

    private function allocationLabel(FeeReceiptAllocation $allocation): string
    {
        $charge = $allocation->charge;

        return $this->learnerName($charge).' · '.$charge->description.' · '.$allocation->receipt->source_reference.' · '.CurrencyMinorUnitFormatter::format($allocation->amount_minor, $allocation->receipt->currency);
    }

    /**
     * @return array<int, string>
     */
    private function searchAvailableReceipts(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $search = '%'.strtoupper(trim($search)).'%';

        return $this->getSchool()->receipts()
            ->whereRaw('school_receipts.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.school_receipt_id = school_receipts.id) - (select coalesce(sum(fee_receipt_allocation_reversals.amount_minor), 0) from fee_receipt_allocation_reversals inner join fee_receipt_allocations on fee_receipt_allocations.id = fee_receipt_allocation_reversals.fee_receipt_allocation_id where fee_receipt_allocations.school_receipt_id = school_receipts.id) + (select coalesce(sum(school_refunds.amount_minor), 0) from school_refunds where school_refunds.school_receipt_id = school_receipts.id and school_refunds.status in (?, ?))', ['approved', 'paid'])
            ->where(function (Builder $query) use ($search): void {
                $query->where('source_reference', 'like', $search)
                    ->orWhereRaw('LOWER(source) LIKE LOWER(?)', [$search]);
            })
            ->withSum('allocations', 'amount_minor')
            ->withSum('allocationReversals as allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['refunds as reserved_refunds_sum_amount_minor' => fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'paid'])], 'amount_minor')
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (SchoolReceipt $receipt): array => [
                $receipt->id => $this->formatReceiptOption($receipt),
            ])
            ->all();
    }

    private function getAvailableReceiptLabel(int $receiptId): ?string
    {
        $receipt = $this->getSchool()->receipts()
            ->whereKey($receiptId)
            ->whereRaw('school_receipts.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.school_receipt_id = school_receipts.id) - (select coalesce(sum(fee_receipt_allocation_reversals.amount_minor), 0) from fee_receipt_allocation_reversals inner join fee_receipt_allocations on fee_receipt_allocations.id = fee_receipt_allocation_reversals.fee_receipt_allocation_id where fee_receipt_allocations.school_receipt_id = school_receipts.id) + (select coalesce(sum(school_refunds.amount_minor), 0) from school_refunds where school_refunds.school_receipt_id = school_receipts.id and school_refunds.status in (?, ?))', ['approved', 'paid'])
            ->withSum('allocations', 'amount_minor')
            ->withSum('allocationReversals as allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['refunds as reserved_refunds_sum_amount_minor' => fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'paid'])], 'amount_minor')
            ->first();

        return $receipt instanceof SchoolReceipt ? $this->formatReceiptOption($receipt) : null;
    }

    /**
     * @return array<int, string>
     */
    private function searchRefundableReceipts(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $search = '%'.trim($search).'%';

        return $this->getSchool()->receipts()
            ->whereRaw('LOWER(source_reference) LIKE LOWER(?)', [$search])
            ->withSum('allocations', 'amount_minor')
            ->withSum('allocationReversals as allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['refunds as reserved_refunds_sum_amount_minor' => fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'paid'])], 'amount_minor')
            ->latest('id')
            ->limit(30)
            ->get()
            ->filter(fn (SchoolReceipt $receipt): bool => $receipt->availableMinor() > 0)
            ->mapWithKeys(fn (SchoolReceipt $receipt): array => [$receipt->id => $this->formatReceiptOption($receipt)])
            ->all();
    }

    private function getRefundableReceiptLabel(int $receiptId): ?string
    {
        $receipt = $this->getSchool()->receipts()
            ->whereKey($receiptId)
            ->withSum('allocations', 'amount_minor')
            ->withSum('allocationReversals as allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['refunds as reserved_refunds_sum_amount_minor' => fn (Builder $query): Builder => $query->whereIn('status', ['approved', 'paid'])], 'amount_minor')
            ->first();

        return $receipt instanceof SchoolReceipt && $receipt->availableMinor() > 0
            ? $this->formatReceiptOption($receipt)
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function getPendingRefundOptions(): array
    {
        return $this->getSchool()->refunds()
            ->where('status', 'pending')
            ->where('requested_by_user_id', '!=', $this->actor()->id)
            ->with('receipt')
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (SchoolRefund $refund): array => [
                $refund->id => '#'.$refund->id.' · '.$refund->receipt->source_reference.' · '.CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency).' · '.$refund->reason,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getApprovedRefundOptions(): array
    {
        return $this->getSchool()->refunds()
            ->where('status', 'approved')
            ->with('receipt')
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (SchoolRefund $refund): array => [
                $refund->id => '#'.$refund->id.' · '.strtoupper($refund->refund_method).' · '.$refund->receipt->source_reference.' · '.CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency),
            ])
            ->all();
    }

    private function getApprovedRefundMethod(int $refundId): ?string
    {
        return $this->getSchool()->refunds()
            ->where('status', 'approved')
            ->whereKey($refundId)
            ->value('refund_method');
    }

    /**
     * @return array<int, string>
     */
    private function searchCreditableCharges(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $search = '%'.trim($search).'%';

        return $this->getSchool()->feeCharges()
            ->where('status', 'posted')
            ->whereRaw('fee_charges.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.fee_charge_id = fee_charges.id) - (select coalesce(sum(fee_receipt_allocation_reversals.amount_minor), 0) from fee_receipt_allocation_reversals inner join fee_receipt_allocations on fee_receipt_allocations.id = fee_receipt_allocation_reversals.fee_receipt_allocation_id where fee_receipt_allocations.fee_charge_id = fee_charges.id) + (select coalesce(sum(fee_adjustments.amount_minor), 0) from fee_adjustments where fee_adjustments.fee_charge_id = fee_charges.id and fee_adjustments.kind = ? and fee_adjustments.status = ?)', ['credit', 'approved'])
            ->whereHas('enrolment.learnerProfile', function (Builder $query) use ($search): void {
                $query->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('preferred_name', 'like', $search);
            })
            ->with(['enrolment.learnerProfile', 'feeSchedule'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->withSum('receiptAllocationReversals as receipt_allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['adjustments as approved_credits_minor' => fn (Builder $query): Builder => $query->where('kind', 'credit')->where('status', 'approved')], 'amount_minor')
            ->latest('id')
            ->limit(30)
            ->get()
            ->mapWithKeys(fn (FeeCharge $charge): array => [
                $charge->id => $this->formatChargeOption($charge),
            ])
            ->all();
    }

    private function getCreditableChargeLabel(int $chargeId): ?string
    {
        $charge = $this->getSchool()->feeCharges()
            ->where('status', 'posted')
            ->whereKey($chargeId)
            ->with(['enrolment.learnerProfile', 'feeSchedule'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->withSum('receiptAllocationReversals as receipt_allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['adjustments as approved_credits_minor' => fn (Builder $query): Builder => $query->where('kind', 'credit')->where('status', 'approved')], 'amount_minor')
            ->first();

        return $charge !== null && $charge->outstandingMinor() > 0
            ? $this->formatChargeOption($charge)
            : null;
    }

    /**
     * @return array<int, string>
     */
    private function getPendingCreditOptions(): array
    {
        return $this->getSchool()->feeAdjustments()
            ->where('status', 'pending')
            ->where('requested_by_user_id', '!=', $this->actor()->id)
            ->with(['charge.enrolment.learnerProfile'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (FeeAdjustment $adjustment): array => [
                $adjustment->id => '#'.$adjustment->id.' · '.$this->learnerName($adjustment->charge).' · '.CurrencyMinorUnitFormatter::format($adjustment->amount_minor, $adjustment->charge->currency).' · '.$adjustment->reason,
            ])
            ->all();
    }

    private function learnerName(FeeCharge $charge): string
    {
        $learner = $charge->enrolment->learnerProfile;

        return trim(($learner->preferred_name ?: $learner->first_name).' '.$learner->last_name);
    }

    private function formatReceiptOption(SchoolReceipt $receipt): string
    {
        $availableMinor = $receipt->availableMinor();

        return strtoupper($receipt->source).' · '.$receipt->source_reference.' · '.CurrencyMinorUnitFormatter::format($availableMinor, $receipt->currency).' available';
    }

    /**
     * @return array<int, string>
     */
    private function searchOutstandingCharges(string $search, Get $get): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $receiptId = filter_var($get('school_receipt_id'), FILTER_VALIDATE_INT);

        if ($receiptId === false || $receiptId === null) {
            return [];
        }

        $school = $this->getSchool();
        $receipt = $school->receipts()->whereKey($receiptId)->first();

        if (! $receipt instanceof SchoolReceipt) {
            return [];
        }

        $search = '%'.trim($search).'%';

        return $school->feeCharges()
            ->where('status', 'posted')
            ->where('currency', $receipt->currency)
            ->whereRaw('fee_charges.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.fee_charge_id = fee_charges.id) - (select coalesce(sum(fee_receipt_allocation_reversals.amount_minor), 0) from fee_receipt_allocation_reversals inner join fee_receipt_allocations on fee_receipt_allocations.id = fee_receipt_allocation_reversals.fee_receipt_allocation_id where fee_receipt_allocations.fee_charge_id = fee_charges.id) + (select coalesce(sum(fee_adjustments.amount_minor), 0) from fee_adjustments where fee_adjustments.fee_charge_id = fee_charges.id and fee_adjustments.kind = ? and fee_adjustments.status = ?)', ['credit', 'approved'])
            ->whereHas('enrolment', function (Builder $query) use ($search): void {
                $query->where(function (Builder $enrolmentQuery) use ($search): void {
                    $enrolmentQuery->whereRaw('LOWER(admission_number) LIKE LOWER(?)', [$search])
                        ->orWhereHas('learnerProfile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery->whereRaw('LOWER(first_name) LIKE LOWER(?)', [$search])
                                ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', [$search])
                                ->orWhereRaw('LOWER(preferred_name) LIKE LOWER(?)', [$search]);
                        });
                });
            })
            ->with(['enrolment.learnerProfile'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->withSum('receiptAllocationReversals as receipt_allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['adjustments as approved_credits_minor' => fn (Builder $query): Builder => $query->where('kind', 'credit')->where('status', 'approved')], 'amount_minor')
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (FeeCharge $charge): array => [
                $charge->id => $this->formatChargeOption($charge),
            ])
            ->all();
    }

    private function getOutstandingChargeLabel(int $chargeId, Get $get): ?string
    {
        $receiptId = filter_var($get('school_receipt_id'), FILTER_VALIDATE_INT);

        if ($receiptId === false || $receiptId === null) {
            return null;
        }

        $school = $this->getSchool();
        $receipt = $school->receipts()->whereKey($receiptId)->first();

        if (! $receipt instanceof SchoolReceipt) {
            return null;
        }

        $charge = $school->feeCharges()
            ->whereKey($chargeId)
            ->where('status', 'posted')
            ->where('currency', $receipt->currency)
            ->whereRaw('fee_charges.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.fee_charge_id = fee_charges.id) - (select coalesce(sum(fee_receipt_allocation_reversals.amount_minor), 0) from fee_receipt_allocation_reversals inner join fee_receipt_allocations on fee_receipt_allocations.id = fee_receipt_allocation_reversals.fee_receipt_allocation_id where fee_receipt_allocations.fee_charge_id = fee_charges.id) + (select coalesce(sum(fee_adjustments.amount_minor), 0) from fee_adjustments where fee_adjustments.fee_charge_id = fee_charges.id and fee_adjustments.kind = ? and fee_adjustments.status = ?)', ['credit', 'approved'])
            ->with(['enrolment.learnerProfile'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->withSum('receiptAllocationReversals as receipt_allocation_reversals_sum_amount_minor', 'fee_receipt_allocation_reversals.amount_minor')
            ->withSum(['adjustments as approved_credits_minor' => fn (Builder $query): Builder => $query->where('kind', 'credit')->where('status', 'approved')], 'amount_minor')
            ->first();

        return $charge instanceof FeeCharge ? $this->formatChargeOption($charge) : null;
    }

    private function formatChargeOption(FeeCharge $charge): string
    {
        $learner = $charge->enrolment->learnerProfile;
        $learnerName = $learner->preferred_name ?: trim($learner->first_name.' '.$learner->last_name);

        return "{$learnerName} · {$charge->enrolment->admission_number} · {$charge->description} · ".CurrencyMinorUnitFormatter::format($charge->outstandingMinor(), $charge->currency).' due';
    }
}
