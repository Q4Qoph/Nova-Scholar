<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $school->name }} · Fees</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Create fee schedule</h3>
                <p class="mt-1 text-sm text-gray-600">Amounts are stored as integer minor units. Posted charges retain this amount snapshot.</p>
                <form method="POST" action="{{ route('schools.fee-schedules.store', $school) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <input name="name" value="{{ old('name') }}" placeholder="Schedule name" required class="rounded-md border-gray-300">
                    <input name="currency" value="{{ old('currency', 'KES') }}" maxlength="3" placeholder="Currency" required class="rounded-md border-gray-300">
                    <input name="amount_minor" value="{{ old('amount_minor') }}" type="number" min="1" placeholder="Amount in minor units" required class="rounded-md border-gray-300">
                    <select name="term_id" class="rounded-md border-gray-300">
                        <option value="">All terms</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                        @endforeach
                    </select>
                    <select name="class_group_id" class="rounded-md border-gray-300">
                        <option value="">All active learners</option>
                        @foreach ($classGroups as $classGroup)
                            <option value="{{ $classGroup->id }}">{{ $classGroup->name }}</option>
                        @endforeach
                    </select>
                    <input name="starts_on" value="{{ old('starts_on') }}" type="date" class="rounded-md border-gray-300">
                    <input name="ends_on" value="{{ old('ends_on') }}" type="date" class="rounded-md border-gray-300">
                    <div class="md:col-span-2"><x-primary-button>Save schedule</x-primary-button></div>
                </form>
                @if ($errors->any())
                    <ul class="mt-4 list-disc pl-5 text-sm text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Record confirmed receipt</h3>
                <p class="mt-1 text-sm text-gray-600">This records a school payment that staff have already confirmed. It does not contact a bank or payment provider.</p>
                <form method="POST" action="{{ route('schools.receipts.store', $school) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <input type="hidden" name="submission_key" value="{{ $receiptSubmissionKey }}">
                    <label class="text-sm text-gray-700">Source<select name="source" required class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="cash" @selected(old('source') === 'cash')>Cash</option>
                        <option value="bank" @selected(old('source') === 'bank')>Bank transfer</option>
                        <option value="mpesa" @selected(old('source') === 'mpesa')>M-Pesa</option>
                    </select></label>
                    <label class="text-sm text-gray-700">Bank/M-Pesa reference<input name="source_reference" value="{{ old('source_reference') }}" maxlength="120" class="mt-1 block w-full rounded-md border-gray-300"><span class="text-xs text-gray-500">Optional for cash; required for bank and M-Pesa.</span></label>
                    <label class="text-sm text-gray-700">Currency<input name="currency" value="{{ old('currency', 'KES') }}" maxlength="3" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <label class="text-sm text-gray-700">Amount in minor units<input name="amount_minor" type="number" min="1" max="{{ PHP_INT_MAX }}" value="{{ old('amount_minor') }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <label class="text-sm text-gray-700">Received on<input name="received_on" type="date" value="{{ old('received_on', today()->toDateString()) }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <label class="text-sm text-gray-700">Evidence note<input name="verification_note" value="{{ old('verification_note') }}" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300"><span class="text-xs text-gray-500">Do not enter private account or payment details.</span></label>
                    <div class="md:col-span-2"><x-primary-button>Record receipt</x-primary-button></div>
                </form>
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Receipts and available balances</h3>
                <div class="mt-4 divide-y divide-gray-200">
                    @forelse ($receipts as $receipt)
                        @php($allocatedReceiptMinor = (int) ($receipt->allocations_sum_amount_minor ?? 0))
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div><p class="font-medium text-gray-900">{{ strtoupper($receipt->source) }} · {{ $receipt->source_reference }}</p><p class="text-sm text-gray-600">{{ $receipt->received_on->toFormattedDateString() }} · manually confirmed by {{ $receipt->verifiedBy->name }}</p></div>
                            <div class="text-sm sm:text-right"><p class="font-semibold text-gray-900">{{ \App\Support\CurrencyMinorUnitFormatter::format($receipt->amount_minor, $receipt->currency) }}</p><p class="text-gray-600">{{ \App\Support\CurrencyMinorUnitFormatter::format($allocatedReceiptMinor, $receipt->currency) }} allocated · {{ \App\Support\CurrencyMinorUnitFormatter::format($receipt->amount_minor - $allocatedReceiptMinor, $receipt->currency) }} available</p></div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-600">No receipts have been recorded.</p>
                    @endforelse
                </div>
                {{ $receipts->links() }}
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Allocate receipts to posted charges</h3>
                <p class="mt-1 text-sm text-gray-600">Each entry allocates part of one receipt to one learner's charge. Any remaining receipt amount stays available as unallocated credit.</p>
                <div class="mt-4 divide-y divide-gray-200">
                    @forelse ($charges as $charge)
                        @php($allocatedChargeMinor = (int) ($charge->receipt_allocations_sum_amount_minor ?? 0))
                        @php($chargeDueMinor = $charge->amount_minor - $allocatedChargeMinor)
                        @if ($chargeDueMinor > 0)
                            @php($availableReceipts = $receipts->getCollection()->filter(fn ($receipt) => $receipt->currency === $charge->currency && $receipt->amount_minor > (int) ($receipt->allocations_sum_amount_minor ?? 0)))
                            <div class="py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="font-medium text-gray-900">{{ $charge->enrolment->learnerProfile->preferred_name ?: $charge->enrolment->learnerProfile->first_name }} {{ $charge->enrolment->learnerProfile->last_name }}</p><p class="text-sm text-gray-600">{{ $charge->description }} · {{ $charge->charged_on->toFormattedDateString() }}</p></div><p class="text-sm font-semibold text-gray-900">{{ \App\Support\CurrencyMinorUnitFormatter::format($chargeDueMinor, $charge->currency) }} due</p></div>
                                @if ($availableReceipts->isNotEmpty())
                                    <form method="POST" action="{{ route('schools.receipts.allocations.store', $school) }}" class="mt-3 grid gap-3 md:grid-cols-3 md:items-end">
                                        @csrf
                                        <input type="hidden" name="allocation_key" value="{{ $allocationKeys[$charge->id] }}">
                                        <label class="text-sm text-gray-700">Receipt<select name="school_receipt_id" required class="mt-1 block w-full rounded-md border-gray-300">
                                            @foreach ($availableReceipts as $receipt)
                                                <option value="{{ $receipt->id }}">{{ strtoupper($receipt->source) }} · {{ $receipt->source_reference }} · {{ \App\Support\CurrencyMinorUnitFormatter::format($receipt->amount_minor - (int) ($receipt->allocations_sum_amount_minor ?? 0), $receipt->currency) }} available</option>
                                            @endforeach
                                        </select></label>
                                        <input type="hidden" name="fee_charge_id" value="{{ $charge->id }}">
                                        <label class="text-sm text-gray-700">Amount in minor units<input name="amount_minor" type="number" min="1" max="{{ min($chargeDueMinor, $availableReceipts->max(fn ($receipt) => $receipt->amount_minor - (int) ($receipt->allocations_sum_amount_minor ?? 0))) }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                                        <div><x-primary-button>Allocate receipt</x-primary-button></div>
                                    </form>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">No matching unallocated {{ $charge->currency }} receipts are available.</p>
                                @endif
                            </div>
                        @endif
                    @empty
                        <p class="py-4 text-sm text-gray-600">No posted charges are available to allocate.</p>
                    @endforelse
                </div>
                {{ $charges->links() }}
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Preview and post charges</h3>
                <form method="POST" action="{{ route('schools.fee-charge-batches.preview', $school) }}" class="mt-4 flex flex-col gap-4 md:flex-row md:items-end">
                    @csrf
                    <label class="flex-1 text-sm text-gray-700">Schedule<select name="fee_schedule_id" required class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($feeSchedules as $feeSchedule)<option value="{{ $feeSchedule->id }}">{{ $feeSchedule->name }} — {{ \App\Support\CurrencyMinorUnitFormatter::format($feeSchedule->amount_minor, $feeSchedule->currency) }}</option>@endforeach
                    </select></label>
                    <label class="flex-1 text-sm text-gray-700">Batch key<input name="batch_key" value="{{ old('batch_key', 'term-'.now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <x-secondary-button type="submit">Preview</x-secondary-button>
                </form>
                @isset($preview)
                    <div class="mt-4 rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                        {{ $preview->eligible_count }} active enrolments · {{ \App\Support\CurrencyMinorUnitFormatter::format($preview->total_minor, $preview->feeSchedule->currency) }} total
                        <form method="POST" action="{{ route('schools.fee-charge-batches.post', $school) }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="fee_schedule_id" value="{{ $preview->fee_schedule_id }}">
                            <input type="hidden" name="batch_key" value="{{ $preview->batch_key }}">
                            <x-primary-button>Post charges</x-primary-button>
                        </form>
                    </div>
                @endisset
            </section>

            <section class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Schedules</h3>
                <div class="mt-4 divide-y divide-gray-200">
                    @forelse ($feeSchedules as $feeSchedule)
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="font-medium text-gray-900">{{ $feeSchedule->name }}</p><p class="text-sm text-gray-600">{{ $feeSchedule->classGroup?->name ?? 'All active learners' }} · {{ $feeSchedule->term?->name ?? 'All terms' }}</p></div>
                            <p class="text-sm font-semibold text-gray-900">{{ \App\Support\CurrencyMinorUnitFormatter::format($feeSchedule->amount_minor, $feeSchedule->currency) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-600">No fee schedules have been created.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
