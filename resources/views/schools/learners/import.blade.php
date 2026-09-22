<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ $school->name }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Review learner import') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a class="text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('schools.learners.index', $school) }}">← {{ __('Back to learner registry') }}</a>
                @if ($batch->status !== 'committed' && $batch->valid_rows > 0)
                    <form method="POST" action="{{ route('schools.learner-imports.commit', [$school, $batch]) }}">
                        @csrf
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Commit valid rows') }}</button>
                    </form>
                @endif
            </div>

            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-indigo-100">{{ __('Private staged batch') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight">{{ $batch->source_filename ?: __('Learner CSV') }}</h2>
                        <p class="mt-2 text-sm text-indigo-100">{{ __('Uploaded :date', ['date' => $batch->created_at->toFormattedDateString()]) }}</p>
                    </div>
                    <span class="w-fit rounded-full bg-white/15 px-3 py-1 text-sm font-medium capitalize">{{ str($batch->status)->replace('_', ' ')->toString() }}</span>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-indigo-100">{{ __('Rows') }}</p><p class="mt-1 text-2xl font-semibold">{{ $batch->total_rows }}</p></div>
                    <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-indigo-100">{{ __('Valid') }}</p><p class="mt-1 text-2xl font-semibold">{{ $batch->valid_rows }}</p></div>
                    <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-indigo-100">{{ __('Invalid') }}</p><p class="mt-1 text-2xl font-semibold">{{ $batch->invalid_rows }}</p></div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <p class="text-sm font-medium text-indigo-600">{{ __('Validation preview') }}</p>
                    <h2 class="mt-1 text-xl font-semibold text-gray-900">{{ __('Review each row before commit') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">{{ __('Row') }}</th>
                                <th class="px-6 py-3 font-medium">{{ __('Learner') }}</th>
                                <th class="px-6 py-3 font-medium">{{ __('Admission') }}</th>
                                <th class="px-6 py-3 font-medium">{{ __('Date of birth') }}</th>
                                <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                                <th class="px-6 py-3 font-medium">{{ __('Validation') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($batch->rows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-500">{{ $row->row_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">{{ $row->payload['preferred_name'] ?: $row->payload['first_name'] }} {{ $row->payload['last_name'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-700">{{ $row->payload['admission_number'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-700">{{ $row->payload['date_of_birth'] ?: __('Not provided') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $row->status === 'valid' ? 'bg-emerald-50 text-emerald-700' : ($row->status === 'committed' ? 'bg-indigo-50 text-indigo-700' : 'bg-red-50 text-red-700') }}">{{ str($row->status)->title() }}</span>
                                    </td>
                                    <td class="min-w-64 px-6 py-4 text-sm text-red-700">
                                        @if ($row->validation_errors)
                                            <ul class="list-disc space-y-1 pl-4">
                                                @foreach ($row->validation_errors as $messages)
                                                    @foreach ($messages as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-500">{{ __('Ready to commit') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
