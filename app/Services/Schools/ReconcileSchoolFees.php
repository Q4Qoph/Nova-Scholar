<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReconcileSchoolFees
{
    /**
     * @return array{generated_at: Carbon, currencies: Collection<string, array{currency: string, posted_charges_minor: int, approved_credits_minor: int, net_allocations_minor: int, outstanding_receivables_minor: int, charge_difference_minor: int, verified_receipts_minor: int, receipt_net_allocations_minor: int, available_receipt_credit_minor: int, approved_refunds_minor: int, paid_refunds_minor: int, receipt_difference_minor: int, pending_or_rejected_refunds_minor: int, unverified_receipts_minor: int}>}
     */
    public function handle(School $school): array
    {
        $totals = collect();
        $getTotals = function (string $currency) use ($totals): array {
            if (! $totals->has($currency)) {
                $totals->put($currency, [
                    'currency' => $currency,
                    'posted_charges_minor' => 0,
                    'approved_credits_minor' => 0,
                    'net_allocations_minor' => 0,
                    'outstanding_receivables_minor' => 0,
                    'charge_difference_minor' => 0,
                    'verified_receipts_minor' => 0,
                    'receipt_net_allocations_minor' => 0,
                    'available_receipt_credit_minor' => 0,
                    'approved_refunds_minor' => 0,
                    'paid_refunds_minor' => 0,
                    'receipt_difference_minor' => 0,
                    'pending_or_rejected_refunds_minor' => 0,
                    'unverified_receipts_minor' => 0,
                ]);
            }

            return $totals->get($currency);
        };

        $charges = $school->feeCharges()
            ->where('status', 'posted')
            ->with([
                'adjustments' => fn ($query) => $query->where('kind', 'credit')->where('status', 'approved'),
                'receiptAllocations' => fn ($query) => $query
                    ->whereHas('receipt', fn ($receiptQuery) => $receiptQuery->whereNotNull('verified_at'))
                    ->with(['reversal', 'receipt:id,currency,verified_at']),
            ])
            ->get();

        foreach ($charges as $charge) {
            $currencyTotals = $getTotals($charge->currency);
            $creditsMinor = (int) $charge->adjustments->sum('amount_minor');
            $allocatedMinor = (int) $charge->receiptAllocations->sum('amount_minor');
            $reversedMinor = (int) $charge->receiptAllocations->sum(fn ($allocation): int => (int) ($allocation->reversal?->amount_minor ?? 0));
            $netAllocatedMinor = $allocatedMinor - $reversedMinor;
            $outstandingMinor = max(0, $charge->amount_minor - $creditsMinor - $allocatedMinor + $reversedMinor);

            $currencyTotals['posted_charges_minor'] += $charge->amount_minor;
            $currencyTotals['approved_credits_minor'] += $creditsMinor;
            $currencyTotals['net_allocations_minor'] += $netAllocatedMinor;
            $currencyTotals['outstanding_receivables_minor'] += $outstandingMinor;
            $totals->put($charge->currency, $currencyTotals);
        }

        $receipts = $school->receipts()->with(['allocations.reversal', 'refunds'])->get();
        foreach ($receipts as $receipt) {
            $currencyTotals = $getTotals($receipt->currency);
            $allocatedMinor = (int) $receipt->allocations->sum('amount_minor');
            $reversedMinor = (int) $receipt->allocations->sum(fn ($allocation): int => (int) ($allocation->reversal?->amount_minor ?? 0));
            $netAllocatedMinor = $allocatedMinor - $reversedMinor;
            $approvedRefundMinor = (int) $receipt->refunds->where('status', 'approved')->sum('amount_minor');
            $paidRefundMinor = (int) $receipt->refunds->where('status', 'paid')->sum('amount_minor');
            $pendingOrRejectedRefundMinor = (int) $receipt->refunds
                ->whereIn('status', ['pending', 'rejected'])
                ->sum('amount_minor');

            if ($receipt->verified_at === null) {
                $currencyTotals['unverified_receipts_minor'] += $receipt->amount_minor;
                $totals->put($receipt->currency, $currencyTotals);

                continue;
            }

            $availableMinor = max(0, $receipt->amount_minor - $allocatedMinor + $reversedMinor - $approvedRefundMinor - $paidRefundMinor);
            $currencyTotals['verified_receipts_minor'] += $receipt->amount_minor;
            $currencyTotals['receipt_net_allocations_minor'] += $netAllocatedMinor;
            $currencyTotals['available_receipt_credit_minor'] += $availableMinor;
            $currencyTotals['approved_refunds_minor'] += $approvedRefundMinor;
            $currencyTotals['paid_refunds_minor'] += $paidRefundMinor;
            $currencyTotals['pending_or_rejected_refunds_minor'] += $pendingOrRejectedRefundMinor;
            $totals->put($receipt->currency, $currencyTotals);
        }

        foreach ($totals as $currency => $currencyTotals) {
            $currencyTotals['charge_difference_minor'] = $currencyTotals['posted_charges_minor']
                - $currencyTotals['approved_credits_minor']
                - $currencyTotals['net_allocations_minor']
                - $currencyTotals['outstanding_receivables_minor'];
            $currencyTotals['receipt_difference_minor'] = $currencyTotals['verified_receipts_minor']
                - $currencyTotals['receipt_net_allocations_minor']
                - $currencyTotals['available_receipt_credit_minor']
                - $currencyTotals['approved_refunds_minor']
                - $currencyTotals['paid_refunds_minor'];
            $totals->put($currency, $currencyTotals);
        }

        return [
            'generated_at' => now($school->timezone ?: config('app.timezone')),
            'currencies' => $totals->sortKeys(),
        ];
    }
}
