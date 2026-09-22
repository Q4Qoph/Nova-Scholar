<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($schedules = $this->getSchedules())
    @php($batches = $this->getBatches())
    @php($charges = $this->getRecentCharges())

    <div class="grid gap-6">
        <section class="rounded-xl bg-gradient-to-br from-primary-600 to-violet-700 p-6 text-white shadow-sm">
            <p class="text-sm font-medium text-primary-100">Finance workspace · {{ $school->name }}</p>
            <h2 class="mt-2 text-2xl font-semibold">Fee operations</h2>
            <p class="mt-2 max-w-2xl text-sm text-primary-100">Review school fee schedules and posted charge batches. Money remains school-scoped and separate from Nova subscriptions.</p>
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
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $schedule->name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $schedule->classGroup?->name ?? 'All active learners' }} · {{ $schedule->term?->name ?? 'All terms' }}</p></div><p class="font-semibold text-gray-950 dark:text-white">{{ $schedule->currency }} {{ number_format($schedule->amount_minor) }}</p></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No fee schedules have been created.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Posting history</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Charge batches</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($batches as $batch)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $batch->feeSchedule->name }} · {{ $batch->batch_key }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $batch->charges_count }} charges · {{ $batch->posted_at?->toFormattedDateString() ?? 'Not posted' }}</p></div><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium capitalize text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ str($batch->status)->replace('_', ' ') }}</span></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No charge batches have been posted.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10"><p class="text-sm font-medium text-primary-600 dark:text-primary-400">Recent records</p><h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Posted charges</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($charges as $charge)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $charge->enrolment->learnerProfile->preferred_name ?: $charge->enrolment->learnerProfile->first_name }} {{ $charge->enrolment->learnerProfile->last_name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $charge->description }} · {{ $charge->charged_on->toFormattedDateString() }}</p></div><p class="font-semibold text-gray-950 dark:text-white">{{ $charge->currency }} {{ number_format($charge->amount_minor) }}</p></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No posted charges have been recorded.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
