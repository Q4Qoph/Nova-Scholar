<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeAdjustment;
use App\Models\FeeCharge;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewSchoolFeeCredit
{
    public function handle(User $actor, School $school, int $adjustmentId, string $decision, ?string $reviewNote): FeeAdjustment
    {
        $decision = strtolower(trim($decision));
        $reviewNote = filled($reviewNote) ? trim((string) $reviewNote) : null;

        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
        }

        if ($decision === 'reject' && $reviewNote === null) {
            throw ValidationException::withMessages(['review_note' => 'A reason is required when rejecting a credit request.']);
        }

        if ($reviewNote !== null && mb_strlen($reviewNote) > 500) {
            throw ValidationException::withMessages(['review_note' => 'The review note cannot exceed 500 characters.']);
        }

        return DB::transaction(function () use ($actor, $school, $adjustmentId, $decision, $reviewNote): FeeAdjustment {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $adjustment = FeeAdjustment::query()
                ->where('school_id', $school->id)
                ->whereKey($adjustmentId)
                ->lockForUpdate()
                ->firstOrFail();

            $targetStatus = $decision === 'approve' ? 'approved' : 'rejected';

            if ($adjustment->status === $targetStatus) {
                if ($adjustment->reviewed_by_user_id === $actor->id
                    && $adjustment->review_note === $reviewNote) {
                    return $adjustment;
                }

                throw ValidationException::withMessages([
                    'adjustment_id' => 'This credit request has already been reviewed.',
                ]);
            }

            if ($adjustment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'adjustment_id' => 'Only pending credit requests can be reviewed.',
                ]);
            }

            if ($adjustment->requested_by_user_id === $actor->id) {
                throw ValidationException::withMessages([
                    'adjustment_id' => 'A different school administrator must review this request.',
                ]);
            }

            $chargeQuery = FeeCharge::query()
                ->where('school_id', $school->id)
                ->whereKey($adjustment->fee_charge_id);

            if ($decision === 'approve') {
                $chargeQuery->lockForUpdate();
            }

            $charge = $chargeQuery->firstOrFail();

            if ($decision === 'approve'
                && ($charge->status !== 'posted' || $adjustment->amount_minor > $charge->outstandingMinor())) {
                throw ValidationException::withMessages([
                    'adjustment_id' => 'The charge balance changed. Recheck the outstanding amount before approving.',
                ]);
            }

            $adjustment->forceFill([
                'status' => $targetStatus,
                'reviewed_by_user_id' => $actor->id,
                'review_note' => $reviewNote,
                'reviewed_at' => now(),
            ])->save();

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_adjustment.'.$targetStatus,
                'auditable_type' => FeeAdjustment::class,
                'auditable_id' => $adjustment->id,
                'metadata' => [
                    'fee_charge_id' => $adjustment->fee_charge_id,
                    'amount_minor' => $adjustment->amount_minor,
                    'currency' => $charge->currency,
                ],
                'occurred_at' => now(),
            ]);

            return $adjustment;
        }, attempts: 3);
    }
}
