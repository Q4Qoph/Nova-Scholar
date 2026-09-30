<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($reconciliation = $this->getReconciliation())

    <div class="grid gap-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Read-only ledger check · {{ $school->name }}</p>
            <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Internal fee reconciliation</h2>
            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-400">This compares posted charges, approved credits, manually confirmed receipts, allocations, reversals and recorded refunds. It does not import bank statements or verify provider payments. Generated {{ $reconciliation['generated_at']->toFormattedDateString() }} at {{ $reconciliation['generated_at']->format('H:i T') }}.</p>
        </section>

        @forelse ($reconciliation['currencies'] as $currency => $totals)
            @php($needsReview = $totals['charge_difference_minor'] !== 0 || $totals['receipt_difference_minor'] !== 0 || $totals['net_allocations_minor'] !== $totals['receipt_net_allocations_minor'] || $totals['pending_or_rejected_refunds_minor'] !== 0 || $totals['unverified_receipts_minor'] !== 0)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <div><p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $currency }}</p><h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Ledger totals</h3></div>
                    <span @class([
                        'rounded-full px-3 py-1 text-sm font-medium',
                        'bg-amber-50 text-amber-800 dark:bg-amber-400/10 dark:text-amber-300' => $needsReview,
                        'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-300' => ! $needsReview,
                    ])>{{ $needsReview ? 'Review needed' : 'Balanced' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-300"><tr><th class="px-5 py-3 font-medium">Measure</th><th class="px-5 py-3 text-right font-medium">Amount</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Posted charges</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['posted_charges_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Approved credits</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['approved_credits_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Net allocations linked to posted charges</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['net_allocations_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Net allocations linked to verified receipts</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['receipt_net_allocations_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Outstanding receivables</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['outstanding_receivables_minor'], $currency) }}</td></tr>
                            <tr @class(['bg-amber-50 dark:bg-amber-400/10' => $totals['charge_difference_minor'] !== 0])><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Charge-side difference</th><td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format(abs($totals['charge_difference_minor']), $currency) }}{{ $totals['charge_difference_minor'] < 0 ? ' credit' : ($totals['charge_difference_minor'] > 0 ? ' debit' : '') }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Manually confirmed receipts</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['verified_receipts_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Available receipt credit</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['available_receipt_credit_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Approved refund reservations</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['approved_refunds_minor'], $currency) }}</td></tr>
                            <tr><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Paid refunds</th><td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ \App\Support\CurrencyMinorUnitFormatter::format($totals['paid_refunds_minor'], $currency) }}</td></tr>
                            <tr @class(['bg-amber-50 dark:bg-amber-400/10' => $totals['receipt_difference_minor'] !== 0])><th class="px-5 py-3 font-medium text-gray-900 dark:text-white">Receipt-side difference</th><td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format(abs($totals['receipt_difference_minor']), $currency) }}{{ $totals['receipt_difference_minor'] < 0 ? ' credit' : ($totals['receipt_difference_minor'] > 0 ? ' debit' : '') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                @if ($totals['pending_or_rejected_refunds_minor'] > 0 || $totals['unverified_receipts_minor'] > 0)
                    <div class="border-t border-amber-200 bg-amber-50 px-6 py-4 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                        <p class="font-semibold">Excluded from settled totals</p>
                        @if ($totals['pending_or_rejected_refunds_minor'] > 0)<p class="mt-1">Pending or rejected refund requests: {{ \App\Support\CurrencyMinorUnitFormatter::format($totals['pending_or_rejected_refunds_minor'], $currency) }}</p>@endif
                        @if ($totals['unverified_receipts_minor'] > 0)<p class="mt-1">Receipts without manual confirmation: {{ \App\Support\CurrencyMinorUnitFormatter::format($totals['unverified_receipts_minor'], $currency) }}</p>@endif
                    </div>
                @endif
            </section>
        @empty
            <section class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-400">No posted charges or receipts are available to reconcile.</section>
        @endforelse
    </div>
</x-filament-panels::page>
