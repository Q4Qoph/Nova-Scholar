<?php

namespace App\Filament\School\Pages;

use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\User;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\CreateSchoolFeeSchedule;
use App\Services\Schools\PostFeeChargeBatch;
use App\Services\Schools\RecordSchoolReceipt;
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
    private function searchAvailableReceipts(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $search = '%'.strtoupper(trim($search)).'%';

        return $this->getSchool()->receipts()
            ->whereRaw('school_receipts.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.school_receipt_id = school_receipts.id)')
            ->where(function (Builder $query) use ($search): void {
                $query->where('source_reference', 'like', $search)
                    ->orWhereRaw('LOWER(source) LIKE LOWER(?)', [$search]);
            })
            ->withSum('allocations', 'amount_minor')
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
            ->whereRaw('school_receipts.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.school_receipt_id = school_receipts.id)')
            ->withSum('allocations', 'amount_minor')
            ->first();

        return $receipt instanceof SchoolReceipt ? $this->formatReceiptOption($receipt) : null;
    }

    private function formatReceiptOption(SchoolReceipt $receipt): string
    {
        $availableMinor = $receipt->amount_minor - (int) ($receipt->allocations_sum_amount_minor ?? 0);

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
            ->whereRaw('fee_charges.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.fee_charge_id = fee_charges.id)')
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
            ->whereRaw('fee_charges.amount_minor > (select coalesce(sum(fee_receipt_allocations.amount_minor), 0) from fee_receipt_allocations where fee_receipt_allocations.fee_charge_id = fee_charges.id)')
            ->with(['enrolment.learnerProfile'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->first();

        return $charge instanceof FeeCharge ? $this->formatChargeOption($charge) : null;
    }

    private function formatChargeOption(FeeCharge $charge): string
    {
        $learner = $charge->enrolment->learnerProfile;
        $learnerName = $learner->preferred_name ?: $learner->first_name.' '.$learner->last_name;
        $dueMinor = $charge->amount_minor - (int) ($charge->receipt_allocations_sum_amount_minor ?? 0);

        return "{$learnerName} · {$charge->enrolment->admission_number} · {$charge->description} · ".CurrencyMinorUnitFormatter::format($dueMinor, $charge->currency).' due';
    }
}
