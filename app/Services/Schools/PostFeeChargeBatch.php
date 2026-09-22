<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostFeeChargeBatch
{
    public function preview(User $actor, School $school, FeeSchedule $feeSchedule, string $batchKey): FeeChargeBatch
    {
        return DB::transaction(function () use ($actor, $school, $feeSchedule, $batchKey): FeeChargeBatch {
            $feeSchedule = FeeSchedule::query()
                ->whereKey($feeSchedule->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureScheduleBelongsToSchool($feeSchedule, $school);
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

            $eligibleCount = $this->eligibleEnrolments($school, $feeSchedule)->count();
            $batch->update([
                'eligible_count' => $eligibleCount,
                'total_minor' => $eligibleCount * $feeSchedule->amount_minor,
            ]);

            return $batch->fresh(['feeSchedule']);
        });
    }

    public function handle(User $actor, School $school, FeeSchedule $feeSchedule, string $batchKey): FeeChargeBatch
    {
        return DB::transaction(function () use ($actor, $school, $feeSchedule, $batchKey): FeeChargeBatch {
            $feeSchedule = FeeSchedule::query()
                ->whereKey($feeSchedule->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureScheduleBelongsToSchool($feeSchedule, $school);
            $batch = FeeChargeBatch::query()
                ->where('school_id', $school->id)
                ->where('batch_key', $batchKey)
                ->lockForUpdate()
                ->first();

            if ($batch?->status === 'posted') {
                return $batch->fresh(['feeSchedule', 'charges']);
            }

            if ($batch !== null && $batch->fee_schedule_id !== $feeSchedule->id) {
                throw ValidationException::withMessages(['batch_key' => 'This batch key already belongs to another fee schedule.']);
            }

            $batch ??= FeeChargeBatch::query()->create([
                'school_id' => $school->id,
                'fee_schedule_id' => $feeSchedule->id,
                'created_by_user_id' => $actor->id,
                'batch_key' => $batchKey,
                'status' => 'draft',
            ]);
            $eligibleEnrolments = $this->eligibleEnrolments($school, $feeSchedule)->get(['id']);
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
                'total_minor' => $eligibleEnrolments->count() * $feeSchedule->amount_minor,
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

    private function ensureScheduleBelongsToSchool(FeeSchedule $feeSchedule, School $school): void
    {
        if ($feeSchedule->term?->school_id !== null && $feeSchedule->term->school_id !== $school->id) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'The fee schedule term must belong to the selected school.']);
        }

        if ($feeSchedule->classGroup?->school_id !== null && $feeSchedule->classGroup->school_id !== $school->id) {
            throw ValidationException::withMessages(['fee_schedule_id' => 'The fee schedule class must belong to the selected school.']);
        }
    }
}
