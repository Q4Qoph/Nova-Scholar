<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordSchoolReceipt
{
    /**
     * @param  array{source: string, source_reference: ?string, submission_key: string, currency: string, amount_minor: int, received_on: string, verification_note: ?string}  $data
     */
    public function handle(User $actor, School $school, array $data): SchoolReceipt
    {
        $data['source'] = strtolower(trim($data['source']));
        $data['currency'] = strtoupper(trim($data['currency']));
        $data['amount_minor'] = (int) $data['amount_minor'];
        $data['source_reference'] = $data['source_reference'] ?? null;
        $data['verification_note'] = $data['verification_note'] ?? null;

        if (! in_array($data['source'], ['cash', 'bank', 'mpesa'], true)
            || preg_match('/^[A-Z]{3}$/', $data['currency']) !== 1
            || $data['amount_minor'] < 1
            || ($data['source'] !== 'cash' && trim((string) $data['source_reference']) === '')) {
            throw ValidationException::withMessages(['source' => 'Receipt source, currency, reference, or amount is invalid.']);
        }

        if ($data['source_reference'] !== null) {
            $data['source_reference'] = strtoupper(trim($data['source_reference']));
        }

        return DB::transaction(function () use ($actor, $school, $data): SchoolReceipt {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $existingSubmission = $school->receipts()->where('submission_key', $data['submission_key'])->first();

            if ($existingSubmission !== null) {
                if ($this->matches($existingSubmission, $data)) {
                    return $existingSubmission;
                }

                throw ValidationException::withMessages([
                    'submission_key' => 'This receipt submission key was already used for different details.',
                ]);
            }

            $sourceReference = $data['source'] === 'cash'
                ? 'CASH-'.$data['submission_key']
                : (string) $data['source_reference'];

            if ($school->receipts()
                ->where('source', $data['source'])
                ->where('source_reference', $sourceReference)
                ->exists()) {
                throw ValidationException::withMessages([
                    'source_reference' => 'A receipt with this source reference has already been recorded.',
                ]);
            }

            $receipt = $school->receipts()->create([
                'verified_by_user_id' => $actor->id,
                'source' => $data['source'],
                'source_reference' => $sourceReference,
                'submission_key' => $data['submission_key'],
                'currency' => $data['currency'],
                'amount_minor' => $data['amount_minor'],
                'received_on' => $data['received_on'],
                'verification_note' => $data['verification_note'],
                'verified_at' => now(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_receipt.recorded',
                'auditable_type' => SchoolReceipt::class,
                'auditable_id' => $receipt->id,
                'metadata' => [
                    'source' => $receipt->source,
                    'currency' => $receipt->currency,
                    'amount_minor' => $receipt->amount_minor,
                ],
                'occurred_at' => now(),
            ]);

            return $receipt;
        }, attempts: 3);
    }

    /**
     * @param  array{source: string, source_reference: ?string, submission_key: string, currency: string, amount_minor: int, received_on: string, verification_note: ?string}  $data
     */
    private function matches(SchoolReceipt $receipt, array $data): bool
    {
        $sourceReference = $data['source'] === 'cash'
            ? 'CASH-'.$data['submission_key']
            : (string) $data['source_reference'];

        return $receipt->source === $data['source']
            && $receipt->source_reference === $sourceReference
            && $receipt->currency === $data['currency']
            && $receipt->amount_minor === $data['amount_minor']
            && $receipt->received_on->toDateString() === $data['received_on']
            && $receipt->verification_note === $data['verification_note'];
    }
}
