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

class ReviewSchoolReceiptRefund
{
    public function handle(User $actor, School $school, int $refundId, string $decision, ?string $reviewNote): SchoolRefund
    {
        $decision = strtolower(trim($decision));
        $reviewNote = $reviewNote === null ? null : trim($reviewNote);
        $reviewNote = $reviewNote === '' ? null : $reviewNote;

        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Select approve or reject.']);
        }

        if ($decision === 'reject' && ($reviewNote === null || $reviewNote === '')) {
            throw ValidationException::withMessages(['review_note' => 'A reason is required when rejecting a refund request.']);
        }

        if ($reviewNote !== null && mb_strlen($reviewNote) > 500) {
            throw ValidationException::withMessages(['review_note' => 'Review notes may not exceed 500 characters.']);
        }

        return DB::transaction(function () use ($actor, $school, $refundId, $decision, $reviewNote): SchoolRefund {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $refundReference = $school->refunds()->whereKey($refundId)->firstOrFail();
            $receipt = $school->receipts()->whereKey($refundReference->school_receipt_id)->lockForUpdate()->firstOrFail();
            $refund = $school->refunds()->whereKey($refundId)->lockForUpdate()->firstOrFail();
            $targetStatus = $decision === 'approve' ? 'approved' : 'rejected';

            if ($refund->status === $targetStatus
                && $refund->reviewed_by_user_id === $actor->id
                && $refund->review_note === $reviewNote) {
                return $refund;
            }

            if ($refund->status !== 'pending') {
                throw ValidationException::withMessages(['refund_id' => 'This refund request has already been reviewed.']);
            }

            if ($refund->requested_by_user_id === $actor->id) {
                throw ValidationException::withMessages(['refund_id' => 'A different school administrator must review this request.']);
            }

            if ($decision === 'approve' && $refund->amount_minor > $receipt->availableMinor()) {
                throw ValidationException::withMessages([
                    'refund_id' => 'The receipt balance changed. Recheck the refundable amount before approving.',
                ]);
            }

            $refund->forceFill([
                'status' => $targetStatus,
                'reviewed_by_user_id' => $actor->id,
                'review_note' => $reviewNote,
                'reviewed_at' => now(),
            ])->save();

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_refund.'.$targetStatus,
                'auditable_type' => SchoolRefund::class,
                'auditable_id' => $refund->id,
                'metadata' => [
                    'school_receipt_id' => $receipt->id,
                    'amount_minor' => $refund->amount_minor,
                    'currency' => $refund->currency,
                ],
                'occurred_at' => now(),
            ]);

            return $refund;
        }, attempts: 3);
    }
}
