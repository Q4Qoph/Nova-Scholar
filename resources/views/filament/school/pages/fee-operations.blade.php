<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($schedules = $this->getSchedules())
    @php($batches = $this->getBatches())
    @php($charges = $this->getRecentCharges())
    @php($adjustments = $this->getRecentAdjustments())
    @php($receipts = $this->getRecentReceipts())
    @php($refunds = $this->getRecentRefunds())
    @php($reversals = $this->getRecentAllocationReversals())

    <div class="grid gap-6">
        <section class="rounded-xl bg-gradient-to-br from-primary-600 to-violet-700 p-6 text-white shadow-sm">
            <p class="text-sm font-medium text-primary-100">Finance workspace · {{ $school->name }}</p>
            <h2 class="mt-2 text-2xl font-semibold">Fee operations</h2>
            <p class="mt-2 max-w-2xl text-sm text-primary-100">Review school fee schedules, posted charges, manually confirmed receipts, and reviewed refunds. Money remains school-scoped and separate from Nova subscriptions.</p>
            <a class="mt-4 inline-flex rounded-md bg-white/15 px-4 py-2 text-sm font-medium text-white hover:bg-white/25" href="{{ route('filament.school.pages.fee-reconciliation', ['tenant' => $school->slug]) }}">Open fee reconciliation</a>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Confirmed manually</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Recent receipts</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($receipts as $receipt)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ strtoupper($receipt->source) }} · {{ $receipt->source_reference }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $receipt->received_on->toFormattedDateString() }} · recorded by {{ $receipt->verifiedBy->name }}</p></div><div class="text-right"><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($receipt->amount_minor, $receipt->currency) }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ \App\Support\CurrencyMinorUnitFormatter::format($receipt->availableMinor(), $receipt->currency) }} available after allocations and approved refunds</p></div></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No receipts have been recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900"><p class="text-sm text-gray-500 dark:text-gray-400">Schedules</p><p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $schedules->count() }}</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900"><p class="text-sm text-gray-500 dark:text-gray-400">Charge batches</p><p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $batches->count() }}</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900"><p class="text-sm text-gray-500 dark:text-gray-400">Posted charges</p><p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $charges->count() }}</p></article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Configured schedules</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Fee schedules</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($schedules as $schedule)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $schedule->name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $schedule->classGroup?->name ?? 'All active learners' }} · {{ $schedule->term?->name ?? 'All terms' }}</p></div><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($schedule->amount_minor, $schedule->currency) }}</p></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No fee schedules have been created.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Posting history</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Charge batches</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($batches as $batch)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $batch->feeSchedule->name }} · {{ $batch->batch_key }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $batch->charges_count }} charges · {{ \App\Support\CurrencyMinorUnitFormatter::format($batch->total_minor, $batch->feeSchedule->currency) }} · {{ $batch->posted_at?->toFormattedDateString() ?? 'Not posted' }}</p></div><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium capitalize text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ str($batch->status)->replace('_', ' ') }}</span></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No charge batches have been posted.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Recent records</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Posted charges</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($charges as $charge)
                    @php($approvedCreditsMinor = (int) ($charge->approved_credits_minor ?? 0))
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $charge->enrolment->learnerProfile->preferred_name ?: $charge->enrolment->learnerProfile->first_name }} {{ $charge->enrolment->learnerProfile->last_name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $charge->description }} · {{ $charge->charged_on->toFormattedDateString() }} · posted {{ \App\Support\CurrencyMinorUnitFormatter::format($charge->amount_minor, $charge->currency) }}</p><a class="mt-1 inline-flex text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400" href="{{ route('schools.fees.statements.show', [$school, $charge->enrolment]) }}">View learner statement</a></div><div class="text-right"><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($charge->outstandingMinor(), $charge->currency) }} outstanding</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ \App\Support\CurrencyMinorUnitFormatter::format($approvedCreditsMinor, $charge->currency) }} approved credits</p></div></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No posted charges have been recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Append-only disbursement history</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">School receipt refunds</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($refunds as $refund)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ strtoupper($refund->refund_method) }} payout · receipt {{ $refund->receipt->source_reference }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $refund->reason }} · requested by {{ $refund->requestedBy->name }} · {{ $refund->requested_at->toFormattedDateString() }}</p>@if ($refund->reviewedBy)<p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($refund->status === 'paid' ? 'approved' : $refund->status) }} by {{ $refund->reviewedBy->name }} · {{ $refund->reviewed_at->toFormattedDateString() }}{{ $refund->review_note ? ' · '.$refund->review_note : '' }}</p>@endif @if ($refund->status === 'paid' && $refund->completedBy)<p class="text-sm text-gray-500 dark:text-gray-400">Payout recorded by {{ $refund->completedBy->name }} · {{ $refund->completed_at->toFormattedDateString() }}{{ $refund->payout_reference ? ' · '.$refund->payout_reference : ' · cash' }}</p>@endif</div><div class="text-right"><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($refund->amount_minor, $refund->currency) }}</p><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium capitalize text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ $refund->status === 'paid' ? 'paid out' : $refund->status }}</span></div></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No refund requests have been recorded.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Append-only allocation history</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Receipt allocation reversals</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($reversals as $reversal)
                    @php($allocation = $reversal->allocation)
                    @php($charge = $allocation->charge)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $charge->enrolment->learnerProfile->preferred_name ?: $charge->enrolment->learnerProfile->first_name }} {{ $charge->enrolment->learnerProfile->last_name }} · {{ $charge->description }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $reversal->reason }} · reversed by {{ $reversal->reversedBy->name }} · {{ $reversal->reversed_at->toFormattedDateString() }}</p><p class="text-sm text-gray-500 dark:text-gray-400">Allocation reversed; no cash refund issued.</p></div><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($reversal->amount_minor, $allocation->receipt->currency) }}</p></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No receipt allocations have been reversed.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Append-only review history</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Charge credit requests</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($adjustments as $adjustment)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $adjustment->charge->enrolment->learnerProfile->preferred_name ?: $adjustment->charge->enrolment->learnerProfile->first_name }} {{ $adjustment->charge->enrolment->learnerProfile->last_name }} · {{ $adjustment->charge->description }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $adjustment->reason }} · requested by {{ $adjustment->requestedBy->name }} · {{ $adjustment->requested_at->toFormattedDateString() }}</p>@if ($adjustment->reviewedBy)<p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($adjustment->status) }} by {{ $adjustment->reviewedBy->name }}{{ $adjustment->review_note ? ' · '.$adjustment->review_note : '' }}</p>@endif</div><div class="text-right"><p class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\CurrencyMinorUnitFormatter::format($adjustment->amount_minor, $adjustment->charge->currency) }}</p><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium capitalize text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ $adjustment->status }}</span></div></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No credit requests have been recorded.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
