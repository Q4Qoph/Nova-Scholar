<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PostFeeChargeBatch
{
    public function preview(User $actor, School $school, FeeSchedule $feeSchedule, string $batchKey): FeeChargeBatch
    {
        return DB::transaction(function () use ($actor, $school, $feeSchedule, $batchKey): FeeChargeBatch {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);
            $feeSchedule = FeeSchedule::query()
                ->whereKey($feeSchedule->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();
            $batch = FeeChargeBatch::query()->firstOrCreate(
                ['school_id' => $school->id, 'batch_key' => $batchKey],
                ['fee_schedule_id' => $feeSchedule->id, 'created_by_user_id' => $actor->id, 'status' => 'draft'],
            );

            if ($batch->fee_schedule_id !== $feeSchedule->id) {
                throw ValidationException::withMessages(['batch_key' => 'This batch key already belongs to another fee schedule.']);
            }
            if ($batch->status === 'posted') {
                return $batch->fresh(['feeSchedule']);
            }

            $this->ensureScheduleCanBeCharged($feeSchedule, $school);
            $eligibleEnrolmentIds = $this->eligibleEnrolments($school, $feeSchedule)
                ->orderBy('enrolments.id')
                ->pluck('enrolments.id');
            $eligibleCount = $eligibleEnrolmentIds->count();
            $batch->update([
                'eligible_count' => $eligibleCount,
                'total_minor' => $this->totalMinor($eligibleCount, $feeSchedule->amount_minor),
                'preview_hash' => $this->previewHash($school, $feeSchedule, $eligibleEnrolmentIds),
                'previewed_at' => now(),
            ]);

            return $batch->fresh(['feeSchedule']);
        });
    }

    public function handle(User $actor, School $school, FeeSchedule $feeSchedule, string $batchKey): FeeChargeBatch
    {
        return DB::transaction(function () use ($actor, $school, $feeSchedule, $batchKey): FeeChargeBatch {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);
            $feeSchedule = FeeSchedule::query()
                ->whereKey($feeSchedule->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();
            $batch = FeeChargeBatch::query()
                ->where('school_id', $school->id)
                ->where('batch_key', $batchKey)
                ->lockForUpdate()
                ->first();

            if ($batch !== null && $batch->fee_schedule_id !== $feeSchedule->id) {
                throw ValidationException::withMessages(['batch_key' => 'This batch key already belongs to another fee schedule.']);
            }

            if ($batch?->status === 'posted') {
                return $batch->fresh(['feeSchedule', 'charges']);
            }

            $this->ensureScheduleCanBeCharged($feeSchedule, $school);

            if ($batch === null || $batch->preview_hash === null) {
                throw ValidationException::withMessages(['batch_key' => 'Preview this fee batch before posting it.']);
            }

            $eligibleEnrolments = $this->eligibleEnrolments($school, $feeSchedule)
                ->orderBy('enrolments.id')
                ->lockForUpdate()
                ->get(['enrolments.id']);

            if (! hash_equals($batch->preview_hash, $this->previewHash($school, $feeSchedule, $eligibleEnrolments->pluck('id')))) {
                throw ValidationException::withMessages(['batch_key' => 'The schedule or eligible learners changed. Preview this batch again before posting it.']);
            }

            $now = now();
            $charges = $eligibleEnrolments->map(fn ($enrolment): array => [
                'school_id' => $school->id,
                'fee_charge_batch_id' => $batch->id,
                'fee_schedule_id' => $feeSchedule->id,
                'enrolment_id' => $enrolment->id,
                'description' => $feeSchedule->name,
                'currency' => $feeSchedule->currency,
                'amount_minor' => $feeSchedule->amount_minor,
                'status' => 'posted',
                'charged_on' => today(),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            if ($charges !== []) {
                $batch->charges()->upsert($charges, ['fee_charge_batch_id', 'enrolment_id'], ['updated_at']);
            }

            $batch->update([
                'eligible_count' => $eligibleEnrolments->count(),
                'total_minor' => $this->totalMinor($eligibleEnrolments->count(), $feeSchedule->amount_minor),
                'status' => 'posted',
                'posted_at' => $now,
            ]);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_charge_batch.posted',
                'auditable_type' => FeeChargeBatch::class,
                'auditable_id' => $batch->id,
                'metadata' => ['batch_key' => $batchKey, 'charge_count' => count($charges), 'total_minor' => $batch->total_minor],
                'occurred_at' => $now,
            ]);

            return $batch->fresh(['feeSchedule', 'charges']);
        });
    }

    private function eligibleEnrolments(School $school, FeeSchedule $feeSchedule): HasMany
    {
        $term = $feeSchedule->term;

        return $school->enrolments()
            ->where('status', 'active')
            ->when($feeSchedule->class_group_id !== null, function (Builder $query) use ($feeSchedule): void {
                $query->whereHas('classMemberships', function (Builder $membershipQuery) use ($feeSchedule): void {
                    $membershipQuery->where('class_group_id', $feeSchedule->class_group_id)
                        ->where('status', 'active')
                        ->whereDate('starts_on', '<=', today())
                        ->where(function (Builder $dateQuery): void {
                            $dateQuery->whereNull('ends_on')->orWhereDate('ends_on', '>=', today());
                        });
                });
            })
            ->when($term !== null, function (Builder $query) use ($term): void {
                $query->whereDate('enrolled_at', '<=', $term->ends_on)
                    ->where(function (Builder $dateQuery) use ($term): void {
                        $dateQuery->whereNull('withdrawn_at')->orWhereDate('withdrawn_at', '>=', $term->starts_on);
                    });
            });
    }

    private function ensureScheduleCanBeCharged(FeeSchedule $feeSchedule, School $school): void
    {
        if ($feeSchedule->status !== 'active') {
            throw ValidationException::withMessages(['fee_schedule_id' => 'Only active fee schedules can be charged.']);
        }

        if (($feeSchedule->starts_on !== null && $feeSchedule->starts_on->isAfter(today()))
            || ($feeSchedule->ends_on !== null && $feeSchedule->ends_on->isBefore(today()))) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'This fee schedule is outside its valid dates.']);
        }

        if ($feeSchedule->term?->school_id !== null && $feeSchedule->term->school_id !== $school->id) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'The fee schedule term must belong to the selected school.']);
        }

        if ($feeSchedule->term !== null && $feeSchedule->term->status !== 'open') {
            throw ValidationException::withMessages(['fee_schedule_id' => 'Only schedules for open terms can be charged.']);
        }

        if ($feeSchedule->classGroup?->school_id !== null && $feeSchedule->classGroup->school_id !== $school->id) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'The fee schedule class must belong to the selected school.']);
        }

        if ($feeSchedule->classGroup !== null && $feeSchedule->classGroup->status !== 'active') {
            throw ValidationException::withMessages(['fee_schedule_id' => 'Only schedules for active classes can be charged.']);
        }
    }

    private function totalMinor(int $eligibleCount, int $amountMinor): int
    {
        if ($amountMinor > 0 && $eligibleCount > intdiv(PHP_INT_MAX, $amountMinor)) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'The total charge exceeds the supported amount.']);
        }

        return $eligibleCount * $amountMinor;
    }

    /**
     * @param  Collection<int, int|string>  $eligibleEnrolmentIds
     */
    private function previewHash(School $school, FeeSchedule $feeSchedule, Collection $eligibleEnrolmentIds): string
    {
        $term = $feeSchedule->term;

        return hash('sha256', json_encode([
            'school_id' => $school->id,
            'fee_schedule_id' => $feeSchedule->id,
            'schedule_name' => $feeSchedule->name,
            'currency' => $feeSchedule->currency,
            'amount_minor' => $feeSchedule->amount_minor,
            'schedule_status' => $feeSchedule->status,
            'schedule_starts_on' => $feeSchedule->starts_on?->toDateString(),
            'schedule_ends_on' => $feeSchedule->ends_on?->toDateString(),
            'term_id' => $term?->id,
            'term_status' => $term?->status,
            'term_starts_on' => $term?->starts_on?->toDateString(),
            'term_ends_on' => $term?->ends_on?->toDateString(),
            'class_group_id' => $feeSchedule->class_group_id,
            'charge_date' => today()->toDateString(),
            'eligible_enrolment_ids' => $eligibleEnrolmentIds->map(fn (int|string $id): int => (int) $id)->sort()->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }
}
