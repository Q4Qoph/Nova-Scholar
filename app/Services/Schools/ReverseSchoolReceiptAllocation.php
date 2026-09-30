<?php

namespace App\Services\Schools;

use App\Models\FeeCharge;
use App\Models\FeeReceiptAllocationReversal;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReverseSchoolReceiptAllocation
{
    public function handle(User $actor, School $school, int $allocationId, string $reason, string $reversalKey): FeeReceiptAllocationReversal
    {
        $reason = trim($reason);
        $reversalKey = trim($reversalKey);

        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => 'Enter a reason of no more than 500 characters.']);
        }

        if ($reversalKey === '' || mb_strlen($reversalKey) > 64) {
            throw ValidationException::withMessages(['reversal_key' => 'Enter a reversal key of no more than 64 characters.']);
        }

        return DB::transaction(function () use ($actor, $school, $allocationId, $reason, $reversalKey): FeeReceiptAllocationReversal {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $allocationReference = $school->feeReceiptAllocations()->whereKey($allocationId)->firstOrFail();
            $receipt = $school->receipts()
                ->whereKey($allocationReference->school_receipt_id)
                ->lockForUpdate()
                ->firstOrFail();
            $charge = FeeCharge::query()
                ->where('school_id', $school->id)
                ->whereKey($allocationReference->fee_charge_id)
                ->lockForUpdate()
                ->firstOrFail();
            $allocation = $school->feeReceiptAllocations()
                ->whereKey($allocationId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingKey = $school->feeReceiptAllocationReversals()
                ->where('reversal_key', $reversalKey)
                ->first();

            if ($existingKey !== null) {
                if ($existingKey->fee_receipt_allocation_id === $allocation->id
                    && $existingKey->amount_minor === $allocation->amount_minor
                    && $existingKey->reason === $reason) {
                    return $existingKey;
                }

                throw ValidationException::withMessages([
                    'reversal_key' => 'This reversal key was already used for different details.',
                ]);
            }

            if ($allocation->reversal()->exists()) {
                throw ValidationException::withMessages([
                    'fee_receipt_allocation_id' => 'This allocation has already been reversed.',
                ]);
            }

            if ($charge->status !== 'posted' || $charge->currency !== $receipt->currency) {
                throw ValidationException::withMessages([
                    'fee_receipt_allocation_id' => 'Only allocations for a posted charge in the receipt currency can be reversed.',
                ]);
            }

            $reversal = $school->feeReceiptAllocationReversals()->create([
                'fee_receipt_allocation_id' => $allocation->id,
                'reversed_by_user_id' => $actor->id,
                'reversal_key' => $reversalKey,
                'amount_minor' => $allocation->amount_minor,
                'reason' => $reason,
                'reversed_at' => now(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_receipt_allocation.reversed',
                'auditable_type' => FeeReceiptAllocationReversal::class,
                'auditable_id' => $reversal->id,
                'metadata' => [
                    'fee_receipt_allocation_id' => $allocation->id,
                    'school_receipt_id' => $receipt->id,
                    'fee_charge_id' => $charge->id,
                    'amount_minor' => $reversal->amount_minor,
                    'currency' => $receipt->currency,
                ],
                'occurred_at' => now(),
            ]);

            return $reversal;
        }, attempts: 3);
    }
}
