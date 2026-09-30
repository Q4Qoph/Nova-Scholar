<x-app-layout>
    @php($enrolment = $statement['enrolment'])
    @php($profile = $enrolment->learnerProfile)
    @php($learnerName = trim(($profile->preferred_name ?: $profile->first_name).' '.$profile->last_name))
    @php($statementUrl = $schoolContext ? route('schools.fees.statements.show', [$statement['school'], $enrolment]) : route('guardian.learners.statements.show', $enrolment))
    @php($csvUrl = $schoolContext ? route($csvRoute, [$statement['school'], $enrolment]) : route($csvRoute, $enrolment))

    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-medium text-indigo-600">{{ $statement['school']->name }}</p>
                <h1 class="text-2xl font-semibold text-gray-900">Fee statement</h1>
                <p class="text-sm text-gray-500">{{ $learnerName }} · Admission {{ $enrolment->admission_number }}</p>
            </div>
            <div class="flex flex-wrap gap-3 print:hidden">
                <a class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" href="{{ $csvUrl.'?'.http_build_query(array_filter(['from' => $statement['from'], 'to' => $statement['to']])) }}">Download CSV</a>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="button" onclick="window.print()">Print statement</button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm print:hidden">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <form action="{{ $statementUrl }}" method="GET" class="grid w-full gap-4 sm:grid-cols-[1fr_1fr_auto]">
                        <label class="text-sm font-medium text-gray-700">From<input class="mt-1 block w-full rounded-md border-gray-300" type="date" name="from" value="{{ $statement['from'] }}"></label>
                        <label class="text-sm font-medium text-gray-700">To<input class="mt-1 block w-full rounded-md border-gray-300" type="date" name="to" value="{{ $statement['to'] }}"></label>
                        <div class="flex items-end"><x-primary-button>Apply period</x-primary-button></div>
                    </form>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-gray-500">Statement reference</dt><dd class="mt-1 font-semibold text-gray-900">{{ $statement['reference'] }}</dd></div>
                    <div><dt class="text-gray-500">Period</dt><dd class="mt-1 font-semibold text-gray-900">{{ $statement['from'] ?? 'Beginning' }} – {{ $statement['to'] ?? 'Present' }}</dd></div>
                    <div><dt class="text-gray-500">School timezone</dt><dd class="mt-1 font-semibold text-gray-900">{{ $statement['timezone'] }}</dd></div>
                    <div><dt class="text-gray-500">Generated</dt><dd class="mt-1 font-semibold text-gray-900">{{ $statement['generated_at']->format('d M Y H:i T') }}</dd></div>
                </dl>
            </section>

            @forelse ($statement['groups'] as $group)
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $group['currency'] }} activity</h2>
                        <p class="text-sm text-gray-600">Opening balance: {{ \App\Support\CurrencyMinorUnitFormatter::format($group['opening_minor'], $group['currency']) }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600"><tr><th class="px-4 py-3 font-medium">Date</th><th class="px-4 py-3 font-medium">Entry</th><th class="px-4 py-3 font-medium">Description</th><th class="px-4 py-3 text-right font-medium">Change</th><th class="px-4 py-3 text-right font-medium">Balance</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($group['rows'] as $row)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($row['date'], $statement['timezone'])->format('d M Y') }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 font-medium capitalize text-gray-900">{{ $row['type'] }}</td>
                                        <td class="min-w-64 px-4 py-3 text-gray-700">{{ $row['description'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium {{ $row['amount_minor'] < 0 ? 'text-emerald-700' : 'text-gray-900' }}">{{ $row['amount_minor'] < 0 ? '−' : '+' }}{{ \App\Support\CurrencyMinorUnitFormatter::format(abs($row['amount_minor']), $group['currency']) }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900">{{ \App\Support\CurrencyMinorUnitFormatter::format($row['running_balance_minor'], $group['currency']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-5 text-gray-500" colspan="5">No entries fall in this period.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                                <tr><th class="px-4 py-3 text-left font-medium text-gray-700" colspan="3">Period activity</th><td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $group['activity_minor'] < 0 ? '−' : '+' }}{{ \App\Support\CurrencyMinorUnitFormatter::format(abs($group['activity_minor']), $group['currency']) }}</td><td></td></tr>
                                <tr><th class="px-4 py-3 text-left font-semibold text-gray-900" colspan="3">Closing receivable</th><td></td><td class="px-4 py-3 text-right font-semibold text-gray-900">{{ \App\Support\CurrencyMinorUnitFormatter::format($group['closing_minor'], $group['currency']) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @empty
                <section class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">No posted fee entries are available for this learner.</section>
            @endforelse

            <p class="text-xs text-gray-500">Statement amounts show posted charges, approved credits and allocations applied to this learner. Unallocated household receipt balances and cash refunds are not included. This statement records the school fee ledger; it does not represent Nova revenue.</p>
        </div>
    </div>
</x-app-layout>
