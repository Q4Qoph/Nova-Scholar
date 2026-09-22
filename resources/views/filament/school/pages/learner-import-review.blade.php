<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($rows = $this->getRows())

    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a class="text-sm font-medium text-primary-600 dark:text-primary-400" href="{{ $this->getRegistryUrl() }}">← Back to learner registry</a>
            @if ($this->canCommit())
                <button wire:click="commitImport" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="button">Commit valid rows</button>
            @endif
        </div>

        <section class="rounded-xl bg-gradient-to-br from-primary-600 to-violet-700 p-6 text-white shadow-sm">
            <p class="text-sm font-medium text-primary-100">Private staged batch · {{ $school->name }}</p>
            <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold">{{ $batch->source_filename ?: 'Learner CSV' }}</h2>
                    <p class="mt-1 text-sm text-primary-100">Uploaded {{ $batch->created_at->toFormattedDateString() }}</p>
                </div>
                <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-medium capitalize">{{ str($batch->status)->replace('_', ' ') }}</span>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-primary-100">Rows</p><p class="mt-1 text-2xl font-semibold">{{ $batch->total_rows }}</p></div>
                <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-primary-100">Valid</p><p class="mt-1 text-2xl font-semibold">{{ $batch->valid_rows }}</p></div>
                <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-primary-100">Invalid</p><p class="mt-1 text-2xl font-semibold">{{ $batch->invalid_rows }}</p></div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Validation preview</p>
                <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Review each row before commit</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-950">
                        <tr><th class="px-6 py-3 font-medium">Row</th><th class="px-6 py-3 font-medium">Learner</th><th class="px-6 py-3 font-medium">Admission</th><th class="px-6 py-3 font-medium">Date of birth</th><th class="px-6 py-3 font-medium">Status</th><th class="px-6 py-3 font-medium">Validation</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-500">{{ $row->row_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-950 dark:text-white">{{ $row->payload['preferred_name'] ?: $row->payload['first_name'] }} {{ $row->payload['last_name'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-700 dark:text-gray-300">{{ $row->payload['admission_number'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-700 dark:text-gray-300">{{ $row->payload['date_of_birth'] ?: 'Not provided' }}</td>
                                <td class="whitespace-nowrap px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium {{ $row->status === 'valid' ? 'bg-emerald-50 text-emerald-700' : ($row->status === 'committed' ? 'bg-primary-50 text-primary-700' : 'bg-danger-50 text-danger-700') }}">{{ str($row->status)->title() }}</span></td>
                                <td class="min-w-64 px-6 py-4 text-sm text-danger-700 dark:text-danger-300">
                                    @if ($row->validation_errors)
                                        <ul class="list-disc space-y-1 pl-4">@foreach ($row->validation_errors as $messages) @foreach ($messages as $message)<li>{{ $message }}</li>@endforeach @endforeach</ul>
                                    @else
                                        <span class="text-gray-500">Ready to commit</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
