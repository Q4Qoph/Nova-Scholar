<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($learners = $this->getLearners())

    <div class="grid gap-6">
        <section class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-1">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $school->name }}</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Learner registry</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Review enrolled learners in this school only.</p>
            </div>
            <a class="inline-flex rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" href="{{ $this->getManagementUrl() }}">Manage registry</a>
        </section>

        @if ($this->canAdmitLearners())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Admissions</p>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Admit a learner</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Create a school-scoped learner record using the existing admission rules.</p>
                </div>
                <form class="mt-5 grid gap-4 md:grid-cols-2" wire:submit="admitLearner">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="first-name">First name</label>
                        <input wire:model="firstName" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="first-name" type="text">
                        @error('firstName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="last-name">Last name</label>
                        <input wire:model="lastName" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="last-name" type="text">
                        @error('lastName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="preferred-name">Preferred name</label>
                        <input wire:model="preferredName" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="preferred-name" type="text">
                        @error('preferredName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="date-of-birth">Date of birth</label>
                        <input wire:model="dateOfBirth" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="date-of-birth" type="date">
                        @error('dateOfBirth')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="admission-number">Admission number</label>
                        <input wire:model="admissionNumber" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="admission-number" type="text">
                        @error('admissionNumber')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end">
                        <button class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Admit learner</button>
                    </div>
                </form>
            </section>
        @endif

        @if ($this->canImportLearners())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Bulk admissions</p>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Stage a learner CSV</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Upload the fixed CSV format, review row validation, then commit valid rows.</p>
                </div>
                <form class="mt-5 flex flex-wrap items-end gap-4" wire:submit="stageLearnerImport">
                    <div class="min-w-72 flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="import-file">CSV file</label>
                        <input wire:model="importFile" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:border-white/10 dark:bg-gray-950 dark:text-white" id="import-file" type="file" accept=".csv,.txt">
                        @error('importFile')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Stage CSV</button>
                </form>
                @if ($importReviewUrl)
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200">
                        CSV staged successfully. <a class="font-semibold underline" href="{{ $importReviewUrl }}">Review validation</a>
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-white/10">
                <h3 class="font-semibold text-gray-950 dark:text-white">Enrolled learners</h3>
                <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ $learners->count() }}</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($learners as $learner)
                    <a class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5" href="{{ $this->getDetailUrl($learner) }}">
                        <div>
                            <p class="font-medium text-gray-950 dark:text-white">{{ $learner->learnerProfile->preferred_name ?: $learner->learnerProfile->first_name }} {{ $learner->learnerProfile->last_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Admission: {{ $learner->admission_number }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium capitalize text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">{{ $learner->status }}</span>
                            <span class="text-sm font-medium text-primary-600 dark:text-primary-400">View →</span>
                        </div>
                    </a>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No learners have been admitted yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
