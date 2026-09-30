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

class RequestSchoolFeeCredit
{
    /**
     * @param  array{adjustment_key: string, amount_minor: int, reason: string}  $data
     */
    public function handle(User $actor, School $school, int $chargeId, array $data): FeeAdjustment
    {
        $data['amount_minor'] = (int) $data['amount_minor'];
        $data['reason'] = trim($data['reason']);

        if ($data['amount_minor'] < 1
            || $data['reason'] === ''
            || mb_strlen($data['reason']) > 500
            || $data['adjustment_key'] === ''
            || mb_strlen($data['adjustment_key']) > 64) {
            throw ValidationException::withMessages([
                'amount_minor' => 'Enter a valid credit amount, request key, and reason.',
            ]);
        }

        return DB::transaction(function () use ($actor, $school, $chargeId, $data): FeeAdjustment {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $charge = FeeCharge::query()
                ->where('school_id', $school->id)
                ->whereKey($chargeId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingAdjustment = $school->feeAdjustments()
                ->where('adjustment_key', $data['adjustment_key'])
                ->first();

            if ($existingAdjustment !== null) {
                if ($existingAdjustment->fee_charge_id === $charge->id
                    && $existingAdjustment->requested_by_user_id === $actor->id
                    && $existingAdjustment->amount_minor === $data['amount_minor']
                    && $existingAdjustment->reason === $data['reason']) {
                    return $existingAdjustment;
                }

                throw ValidationException::withMessages([
                    'adjustment_key' => 'This credit request key was already used for different details.',
                ]);
            }

            if ($charge->status !== 'posted') {
                throw ValidationException::withMessages([
                    'fee_charge_id' => 'Credits can only be requested for posted charges.',
                ]);
            }

            if ($data['amount_minor'] > $charge->outstandingMinor()) {
                throw ValidationException::withMessages([
                    'amount_minor' => 'The credit exceeds the charge’s outstanding balance.',
                ]);
            }

            $adjustment = $school->feeAdjustments()->create([
                'fee_charge_id' => $charge->id,
                'requested_by_user_id' => $actor->id,
                'adjustment_key' => $data['adjustment_key'],
                'kind' => 'credit',
                'status' => 'pending',
                'amount_minor' => $data['amount_minor'],
                'reason' => $data['reason'],
                'requested_at' => now(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_adjustment.requested',
                'auditable_type' => FeeAdjustment::class,
                'auditable_id' => $adjustment->id,
                'metadata' => [
                    'fee_charge_id' => $charge->id,
                    'kind' => $adjustment->kind,
                    'amount_minor' => $adjustment->amount_minor,
                    'currency' => $charge->currency,
                ],
                'occurred_at' => now(),
            ]);

            return $adjustment;
        }, attempts: 3);
    }
}
