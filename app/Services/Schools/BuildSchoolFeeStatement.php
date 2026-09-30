<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BuildSchoolFeeStatement
{
    /**
     * @return array{
     *     school: School,
     *     enrolment: Enrolment,
     *     timezone: string,
     *     from: ?string,
     *     to: ?string,
     *     reference: string,
     *     generated_at: CarbonImmutable,
     *     groups: Collection<string, array{currency: string, opening_minor: int, activity_minor: int, closing_minor: int, rows: array<int, array{date: string, type: string, description: string, amount_minor: int, running_balance_minor: int, source_id: int}>}>
     * }
     */
    public function handle(School $school, Enrolment $enrolment, ?string $from = null, ?string $to = null): array
    {
        $enrolment->loadMissing('learnerProfile');
        $timezone = $school->timezone ?: config('app.timezone');
        $transactions = collect();

        $charges = $school->feeCharges()
            ->where('enrolment_id', $enrolment->id)
            ->where('status', 'posted')
            ->with([
                'adjustments' => fn ($query) => $query->where('kind', 'credit')->where('status', 'approved'),
                'receiptAllocations' => fn ($query) => $query
                    ->whereHas('receipt', fn ($receiptQuery) => $receiptQuery->whereNotNull('verified_at'))
                    ->with(['receipt:id,currency,verified_at', 'reversal']),
            ])
            ->get();

        foreach ($charges as $charge) {
            $transactions->push($this->transaction(
                id: $charge->id,
                type: 'charge',
                date: $charge->charged_on->toDateString(),
                description: $charge->description,
                amountMinor: $charge->amount_minor,
                currency: $charge->currency,
                timezone: $timezone,
            ));

            foreach ($charge->adjustments as $adjustment) {
                $transactions->push($this->transaction(
                    id: $adjustment->id,
                    type: 'credit',
                    date: $adjustment->reviewed_at,
                    description: 'Approved credit · '.$adjustment->reason,
                    amountMinor: -$adjustment->amount_minor,
                    currency: $charge->currency,
                    timezone: $timezone,
                ));
            }

            foreach ($charge->receiptAllocations as $allocation) {
                $transactions->push($this->transaction(
                    id: $allocation->id,
                    type: 'allocation',
                    date: $allocation->allocated_at,
                    description: 'Manually confirmed receipt allocation',
                    amountMinor: -$allocation->amount_minor,
                    currency: $allocation->receipt->currency,
                    timezone: $timezone,
                ));

                if ($allocation->reversal !== null) {
                    $transactions->push($this->transaction(
                        id: $allocation->reversal->id,
                        type: 'reversal',
                        date: $allocation->reversal->reversed_at,
                        description: 'Allocation reversal · '.$allocation->reversal->reason,
                        amountMinor: $allocation->reversal->amount_minor,
                        currency: $allocation->receipt->currency,
                        timezone: $timezone,
                    ));
                }
            }
        }

        $typeOrder = ['charge' => 1, 'credit' => 2, 'allocation' => 3, 'reversal' => 4];
        $transactions = $transactions
            ->sortBy(fn (array $transaction): string => sprintf('%s|%02d|%010d', $transaction['date'], $typeOrder[$transaction['type']], $transaction['source_id']))
            ->values();
        $periodRows = $transactions->filter(fn (array $transaction): bool => ($from === null || $transaction['date'] >= $from)
            && ($to === null || $transaction['date'] <= $to)
        )->values();
        $referenceRows = $transactions->filter(fn (array $transaction): bool => $to === null || $transaction['date'] <= $to)
            ->map(fn (array $transaction): array => [
                $transaction['date'],
                $transaction['type'],
                $transaction['source_id'],
                $transaction['currency'],
                $transaction['amount_minor'],
                $transaction['description'],
            ])
            ->all();

        $groups = collect();
        foreach ($transactions
            ->filter(fn (array $transaction): bool => $to === null || $transaction['date'] <= $to)
            ->groupBy('currency') as $currency => $currencyTransactions) {
            $openingMinor = $currencyTransactions
                ->filter(fn (array $transaction): bool => $from !== null && $transaction['date'] < $from)
                ->sum('amount_minor');
            $activityRows = $periodRows->where('currency', $currency)->values();
            $activityMinor = $activityRows->sum('amount_minor');
            $runningBalanceMinor = $openingMinor;
            $rows = $activityRows->map(function (array $transaction) use (&$runningBalanceMinor): array {
                $runningBalanceMinor += $transaction['amount_minor'];

                return [...$transaction, 'running_balance_minor' => $runningBalanceMinor];
            })->all();

            $groups->put($currency, [
                'currency' => $currency,
                'opening_minor' => $openingMinor,
                'activity_minor' => $activityMinor,
                'closing_minor' => $openingMinor + $activityMinor,
                'rows' => $rows,
            ]);
        }

        $referencePayload = json_encode([
            'school_id' => $school->id,
            'enrolment_id' => $enrolment->id,
            'from' => $from,
            'to' => $to,
            'entries' => $referenceRows,
        ], JSON_THROW_ON_ERROR);

        return [
            'school' => $school,
            'enrolment' => $enrolment,
            'timezone' => $timezone,
            'from' => $from,
            'to' => $to,
            'reference' => 'NSF-'.strtoupper(substr(hash('sha256', $referencePayload), 0, 16)),
            'generated_at' => CarbonImmutable::now($timezone),
            'groups' => $groups,
        ];
    }

    /** @return array{date: string, type: string, description: string, amount_minor: int, currency: string, source_id: int} */
    private function transaction(
        int $id,
        string $type,
        mixed $date,
        string $description,
        int $amountMinor,
        string $currency,
        string $timezone,
    ): array {
        $localDate = $date instanceof CarbonImmutable
            ? $date->setTimezone($timezone)->toDateString()
            : ($date instanceof \DateTimeInterface
                ? CarbonImmutable::instance($date)->setTimezone($timezone)->toDateString()
                : (string) $date);

        return [
            'date' => $localDate,
            'type' => $type,
            'description' => $description,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'source_id' => $id,
        ];
    }
}
