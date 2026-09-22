<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('School registry') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $school->name }} {{ __('learners') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 lg:col-span-3" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($canImport)
                <section class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-6 lg:col-span-3">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Staged import') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Add learners from CSV') }}</h2>
                            <p class="max-w-2xl text-sm leading-6 text-gray-600">{{ __('Upload a CSV for validation before anything is added to the registry. Required columns: first_name, last_name, preferred_name, date_of_birth, admission_number.') }}</p>
                        </div>
                        <a class="text-sm font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('schools.academic.index', $school) }}">{{ __('Review academic structure') }} →</a>
                    </div>
                    <form class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end" method="POST" action="{{ route('schools.learner-imports.store', $school) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <label class="block text-sm font-medium text-gray-700" for="learner-import-file">{{ __('Learner CSV file') }}</label>
                            <input class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-indigo-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700" id="learner-import-file" name="file" type="file" accept=".csv,.txt,text/csv" required>
                            @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Stage CSV') }}</button>
                    </form>
                    @if ($importBatches->isNotEmpty())
                        <div class="mt-5 border-t border-indigo-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Recent imports') }}</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                                @foreach ($importBatches as $importBatch)
                                    <a class="text-sm font-medium text-indigo-700 hover:text-indigo-900" href="{{ route('schools.learner-imports.show', [$school, $importBatch]) }}">{{ $importBatch->source_filename ?: __('Unnamed CSV') }} · {{ str($importBatch->status)->replace('_', ' ')->title() }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($canAdmit)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Admission') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Admit learner') }}</h2>
                    </div>

                    <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.learners.store', $school) }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="first-name">{{ __('First name') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="first-name" name="first_name" type="text" value="{{ old('first_name') }}" required>
                            @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="last-name">{{ __('Last name') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="last-name" name="last_name" type="text" value="{{ old('last_name') }}" required>
                            @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="preferred-name">{{ __('Preferred name') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="preferred-name" name="preferred_name" type="text" value="{{ old('preferred_name') }}">
                            @error('preferred_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="date-of-birth">{{ __('Date of birth') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="date-of-birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="admission-number">{{ __('Admission number') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="admission-number" name="admission_number" type="text" value="{{ old('admission_number') }}" required>
                            @error('admission_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Admit learner') }}</button>
                    </form>
                </section>
            @endif

            <section class="{{ $canAdmit ? 'lg:col-span-2' : 'lg:col-span-3' }} rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Registry') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Enrolled learners') }}</h2>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">{{ $learners->count() }}</span>
                </div>

                <div class="mt-6 divide-y divide-gray-200">
                    @forelse ($learners as $learner)
                        <a class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:bg-gray-50" href="{{ route('schools.learners.show', [$school, $learner]) }}">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $learner->learnerProfile->preferred_name ?: $learner->learnerProfile->first_name }} {{ $learner->learnerProfile->last_name }}</p>
                                <p class="text-sm text-gray-500">{{ __('Admission') }}: {{ $learner->admission_number }}</p>
                            </div>
                            <span class="text-sm font-medium text-indigo-600">{{ __('View') }} →</span>
                        </a>
                    @empty
                        <p class="py-4 text-sm text-gray-500">{{ __('No learners have been admitted yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
