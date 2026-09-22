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
                <h3 class="text-lg font-semibold text-gray-900">Preview and post charges</h3>
                <form method="POST" action="{{ route('schools.fee-charge-batches.preview', $school) }}" class="mt-4 flex flex-col gap-4 md:flex-row md:items-end">
                    @csrf
                    <label class="flex-1 text-sm text-gray-700">Schedule<select name="fee_schedule_id" required class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach ($feeSchedules as $feeSchedule)<option value="{{ $feeSchedule->id }}">{{ $feeSchedule->name }} — {{ $feeSchedule->currency }} {{ number_format($feeSchedule->amount_minor) }}</option>@endforeach
                    </select></label>
                    <label class="flex-1 text-sm text-gray-700">Batch key<input name="batch_key" value="{{ old('batch_key', 'term-'.now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <x-secondary-button>Preview</x-secondary-button>
                </form>
                @isset($preview)
                    <div class="mt-4 rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                        {{ $preview->eligible_count }} active enrolments · {{ $preview->feeSchedule->currency }} {{ number_format($preview->total_minor) }} total
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
                            <p class="text-sm font-semibold text-gray-900">{{ $feeSchedule->currency }} {{ number_format($feeSchedule->amount_minor) }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-600">No fee schedules have been created.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
