<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompleteSchoolReceiptRefund
{
    public function handle(
        User $actor,
        School $school,
        int $refundId,
        string $completionKey,
        ?string $payoutReference,
    ): SchoolRefund {
        $completionKey = trim($completionKey);
        $payoutReference = $payoutReference === null ? null : trim($payoutReference);

        if ($payoutReference !== null) {
            $payoutReference = $payoutReference === '' ? null : strtoupper($payoutReference);
        }

        if ($completionKey === '' || mb_strlen($completionKey) > 64) {
            throw ValidationException::withMessages(['completion_key' => 'Enter a valid payout completion key.']);
        }

        if ($payoutReference !== null && mb_strlen($payoutReference) > 120) {
            throw ValidationException::withMessages(['payout_reference' => 'Payout references may not exceed 120 characters.']);
        }

        return DB::transaction(function () use ($actor, $school, $refundId, $completionKey, $payoutReference): SchoolRefund {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $refundReference = $school->refunds()->whereKey($refundId)->firstOrFail();
            $receipt = $school->receipts()->whereKey($refundReference->school_receipt_id)->lockForUpdate()->firstOrFail();
            $refund = $school->refunds()->whereKey($refundId)->lockForUpdate()->firstOrFail();

            if ($refund->status === 'paid'
                && $refund->completion_key === $completionKey
                && $refund->payout_reference === $payoutReference
                && $refund->completed_by_user_id === $actor->id) {
                return $refund;
            }

            if ($refund->status !== 'approved') {
                throw ValidationException::withMessages(['refund_id' => 'Only an approved refund can be recorded as paid.']);
            }

            if ($refund->refund_method !== 'cash' && ($payoutReference === null || $payoutReference === '')) {
                throw ValidationException::withMessages(['payout_reference' => 'A bank or M-Pesa payout reference is required.']);
            }

            $duplicateCompletion = $school->refunds()->where('completion_key', $completionKey)->exists();

            if ($duplicateCompletion) {
                throw ValidationException::withMessages(['completion_key' => 'This payout completion key has already been used.']);
            }

            if ($payoutReference !== null && $payoutReference !== '' && $school->refunds()
                ->where('refund_method', $refund->refund_method)
                ->where('payout_reference', $payoutReference)
                ->where('id', '!=', $refund->id)
                ->exists()) {
                throw ValidationException::withMessages(['payout_reference' => 'This payout reference is already recorded for another refund.']);
            }

            $refund->forceFill([
                'status' => 'paid',
                'completion_key' => $completionKey,
                'payout_reference' => $payoutReference === '' ? null : $payoutReference,
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
            ])->save();

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_refund.paid',
                'auditable_type' => SchoolRefund::class,
                'auditable_id' => $refund->id,
                'metadata' => [
                    'school_receipt_id' => $receipt->id,
                    'amount_minor' => $refund->amount_minor,
                    'currency' => $refund->currency,
                    'refund_method' => $refund->refund_method,
                ],
                'occurred_at' => now(),
            ]);

            return $refund;
        }, attempts: 3);
    }
}
