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

class RequestSchoolReceiptRefund
{
    /**
     * @param  array{refund_key: string, amount_minor: int, refund_method: string, reason: string}  $data
     */
    public function handle(User $actor, School $school, int $receiptId, array $data): SchoolRefund
    {
        $data['refund_key'] = trim($data['refund_key']);
        $data['amount_minor'] = (int) $data['amount_minor'];
        $data['refund_method'] = strtolower(trim($data['refund_method']));
        $data['reason'] = trim($data['reason']);

        if ($data['refund_key'] === '' || mb_strlen($data['refund_key']) > 64) {
            throw ValidationException::withMessages(['refund_key' => 'Enter a valid refund request key.']);
        }

        if (! in_array($data['refund_method'], ['cash', 'bank', 'mpesa'], true)) {
            throw ValidationException::withMessages(['refund_method' => 'Select a supported manual payout method.']);
        }

        if ($data['amount_minor'] < 1) {
            throw ValidationException::withMessages(['amount_minor' => 'Refund amount must be greater than zero.']);
        }

        if ($data['reason'] === '' || mb_strlen($data['reason']) > 500) {
            throw ValidationException::withMessages(['reason' => 'Enter a reason of no more than 500 characters.']);
        }

        return DB::transaction(function () use ($actor, $school, $receiptId, $data): SchoolRefund {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $receipt = $school->receipts()->whereKey($receiptId)->lockForUpdate()->firstOrFail();
            $existingRefund = $school->refunds()->where('refund_key', $data['refund_key'])->first();

            if ($existingRefund !== null) {
                if ($existingRefund->school_receipt_id === $receipt->id
                    && $existingRefund->requested_by_user_id === $actor->id
                    && $existingRefund->currency === $receipt->currency
                    && $existingRefund->amount_minor === $data['amount_minor']
                    && $existingRefund->refund_method === $data['refund_method']
                    && $existingRefund->reason === $data['reason']) {
                    return $existingRefund;
                }

                throw ValidationException::withMessages([
                    'refund_key' => 'This refund key was already used for different details.',
                ]);
            }

            if ($data['amount_minor'] > $receipt->availableMinor()) {
                throw ValidationException::withMessages([
                    'amount_minor' => 'The refund exceeds the unallocated receipt balance.',
                ]);
            }

            $refund = $school->refunds()->create([
                'school_receipt_id' => $receipt->id,
                'requested_by_user_id' => $actor->id,
                'refund_key' => $data['refund_key'],
                'status' => 'pending',
                'refund_method' => $data['refund_method'],
                'currency' => $receipt->currency,
                'amount_minor' => $data['amount_minor'],
                'reason' => $data['reason'],
                'requested_at' => now(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_refund.requested',
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
