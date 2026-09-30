<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeCharge;
use App\Models\FeeReceiptAllocation;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AllocateSchoolReceipt
{
    public function handle(
        User $actor,
        School $school,
        int $receiptId,
        int $chargeId,
        int $amountMinor,
        string $allocationKey,
    ): FeeReceiptAllocation {
        if ($amountMinor < 1) {
            throw ValidationException::withMessages(['amount_minor' => 'Allocation amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($actor, $school, $receiptId, $chargeId, $amountMinor, $allocationKey): FeeReceiptAllocation {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $receipt = SchoolReceipt::query()
                ->where('school_id', $school->id)
                ->whereKey($receiptId)
                ->lockForUpdate()
                ->firstOrFail();
            $charge = FeeCharge::query()
                ->where('school_id', $school->id)
                ->whereKey($chargeId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingAllocation = $school->feeReceiptAllocations()->where('allocation_key', $allocationKey)->first();

            if ($existingAllocation !== null) {
                if ($existingAllocation->school_receipt_id === $receipt->id
                    && $existingAllocation->fee_charge_id === $charge->id
                    && $existingAllocation->amount_minor === $amountMinor) {
                    return $existingAllocation;
                }

                throw ValidationException::withMessages([
                    'allocation_key' => 'This allocation key was already used for different details.',
                ]);
            }

            if ($charge->status !== 'posted' || $charge->currency !== $receipt->currency) {
                throw ValidationException::withMessages([
                    'fee_charge_id' => 'Select a posted charge in the receipt currency.',
                ]);
            }

            $receiptAvailable = $receipt->availableMinor();
            $chargeDue = $charge->outstandingMinor();

            if ($amountMinor > $receiptAvailable) {
                throw ValidationException::withMessages([
                    'amount_minor' => 'The allocation exceeds the unallocated receipt balance.',
                ]);
            }

            if ($amountMinor > $chargeDue) {
                throw ValidationException::withMessages([
                    'amount_minor' => 'The allocation exceeds the outstanding charge balance.',
                ]);
            }

            $allocation = $school->feeReceiptAllocations()->create([
                'school_receipt_id' => $receipt->id,
                'fee_charge_id' => $charge->id,
                'allocated_by_user_id' => $actor->id,
                'allocation_key' => $allocationKey,
                'amount_minor' => $amountMinor,
                'allocated_at' => now(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_receipt.allocated',
                'auditable_type' => FeeReceiptAllocation::class,
                'auditable_id' => $allocation->id,
                'metadata' => [
                    'school_receipt_id' => $receipt->id,
                    'fee_charge_id' => $charge->id,
                    'amount_minor' => $allocation->amount_minor,
                    'currency' => $receipt->currency,
                ],
                'occurred_at' => now(),
            ]);

            return $allocation;
        }, attempts: 3);
    }
}
