<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ $school->name }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Learner record') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <a class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('schools.learners.index', $school) }}">← {{ __('Back to learner registry') }}</a>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            @if (session('activation_url'))
                <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                    <p class="font-semibold">{{ __('One-time learner activation link') }}</p>
                    <p>{{ __('Email delivery is not configured. Share this link securely with the learner; it will expire after 24 hours and can be used once.') }}</p>
                    <a class="break-all font-medium underline" href="{{ session('activation_url') }}">{{ session('activation_url') }}</a>
                </div>
            @endif

            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">{{ __('Active enrolment') }}</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">{{ $learner->learnerProfile->preferred_name ?: $learner->learnerProfile->first_name }} {{ $learner->learnerProfile->last_name }}</h2>
                <p class="mt-3 text-indigo-100">{{ __('Admission number') }}: {{ $learner->admission_number }}</p>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Enrolment status') }}</p>
                    <p class="mt-2 text-lg font-semibold capitalize text-gray-900">{{ $learner->status }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Enrolled on') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $learner->enrolled_at->toFormattedDateString() }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Date of birth') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $learner->learnerProfile->date_of_birth?->toFormattedDateString() ?? __('Not recorded') }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('School') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $school->name }}</p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
                @if ($canAssignClass)
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Academic placement') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Assign to a class') }}</h2>
                            <p class="text-sm leading-6 text-gray-600">{{ __('Use dates to preserve this learner’s class history.') }}</p>
                        </div>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.learners.class-memberships.store', [$school, $learner]) }}">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="class-group">{{ __('Class group') }}</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="class-group" name="class_group_id" required>
                                    <option value="">{{ __('Select class') }}</option>
                                    @foreach ($academicYears as $academicYear)
                                        @foreach ($academicYear->classGroups as $classGroup)
                                            <option value="{{ $classGroup->id }}">{{ $academicYear->name }} · {{ $classGroup->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                                @error('class_group_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="starts-on">{{ __('Starts on') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="starts-on" name="starts_on" type="date" value="{{ old('starts_on', today()->toDateString()) }}" required>
                                @error('starts_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="ends-on">{{ __('Ends on (optional)') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="ends-on" name="ends_on" type="date" value="{{ old('ends_on') }}">
                                @error('ends_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Save class placement') }}</button>
                        </form>
                    </article>
                @endif

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm {{ $canAssignClass ? '' : 'lg:col-span-2' }}">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Placement history') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Classes') }}</h2>
                    </div>
                    <div class="mt-5 divide-y divide-gray-100">
                        @forelse ($classMemberships as $classMembership)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $classMembership->classGroup->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $classMembership->classGroup->academicYear->name }} · {{ $classMembership->starts_on->toFormattedDateString() }} – {{ $classMembership->ends_on?->toFormattedDateString() ?? __('Current') }}</p>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium capitalize text-indigo-700">{{ $classMembership->status }}</span>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-gray-500">{{ __('No class placements have been recorded yet.') }}</p>
                        @endforelse
                    </div>
                </article>
            </section>

            @if ($canManageLifecycle)
                <section class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Progression') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Promote to a new class') }}</h2>
                            <p class="text-sm leading-6 text-gray-600">{{ __('The current placement will end the day before the new placement begins.') }}</p>
                        </div>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.learners.promote', [$school, $learner]) }}">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="promotion-class-group">{{ __('New class group') }}</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="promotion-class-group" name="class_group_id" required>
                                    <option value="">{{ __('Select class') }}</option>
                                    @foreach ($academicYears as $academicYear)
                                        @foreach ($academicYear->classGroups as $classGroup)
                                            <option value="{{ $classGroup->id }}">{{ $academicYear->name }} · {{ $classGroup->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                                @error('class_group_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="promotion-starts-on">{{ __('Promotion date') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="promotion-starts-on" name="starts_on" type="date" value="{{ old('starts_on', today()->toDateString()) }}" required>
                                @error('starts_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Record promotion') }}</button>
                        </form>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Lifecycle') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Transfer learner') }}</h2>
                            <p class="text-sm leading-6 text-gray-600">{{ __('You must be an administrator in both schools. Source history is retained.') }}</p>
                        </div>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.learners.transfer', [$school, $learner]) }}">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="destination-school">{{ __('Destination school') }}</label>
                                <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="destination-school" name="destination_school_id" required>
                                    <option value="">{{ __('Select school') }}</option>
                                    @foreach ($transferSchools as $transferSchool)
                                        <option value="{{ $transferSchool->id }}">{{ $transferSchool->name }}</option>
                                    @endforeach
                                </select>
                                @error('destination_school_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="transfer-admission-number">{{ __('New admission number') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="transfer-admission-number" name="admission_number" type="text" required>
                                @error('admission_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="transferred-on">{{ __('Transfer date') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="transferred-on" name="transferred_on" type="date" value="{{ old('transferred_on', today()->toDateString()) }}" required>
                                @error('transferred_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Transfer learner') }}</button>
                        </form>
                    </article>
                </section>

                <section class="rounded-xl border border-red-200 bg-red-50 p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-700">{{ __('Access and enrolment control') }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-red-900">{{ __('Deactivate learner') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-red-800">{{ __('This withdraws the enrolment and blocks managed learner access. History is retained and reactivation is not yet available.') }}</p>
                        </div>
                        <form method="POST" action="{{ route('schools.learners.deactivate', [$school, $learner]) }}">
                            @csrf
                            <input name="deactivated_on" type="hidden" value="{{ today()->toDateString() }}">
                            <button class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 sm:w-auto" type="submit">{{ __('Deactivate') }}</button>
                        </form>
                    </div>
                </section>
            @endif

            @if ($canManageAccess)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Managed learner access') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Learner sign-in') }}</h2>
                            <p class="text-sm leading-6 text-gray-600">{{ __('Learners use a generated login ID and their own password. An email address is not required.') }}</p>
                        </div>
                        @if ($learner->learnerProfile->user)
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $learner->learnerProfile->user->learner_activated_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $learner->learnerProfile->user->learner_activated_at ? __('Activated') : __('Awaiting activation') }}</span>
                        @endif
                    </div>
                    @if ($learner->learnerProfile->user)
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm">
                            <p class="text-gray-500">{{ __('Learner login ID') }}</p>
                            <p class="mt-1 font-semibold tracking-wide text-gray-900">{{ $learner->learnerProfile->user->learner_login_id }}</p>
                        </div>
                    @else
                        <form class="mt-5" method="POST" action="{{ route('schools.learners.access.store', [$school, $learner]) }}">
                            @csrf
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Issue activation link') }}</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($canManageGuardians)
                <section class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Relationship verification') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Link a guardian') }}</h2>
                        </div>
                        <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.learners.guardians.store', [$school, $learner]) }}">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="guardian-email">{{ __('Verified user email') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="guardian-email" name="email" type="email" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="guardian-relationship">{{ __('Relationship') }}</label>
                                <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="guardian-relationship" name="relationship" type="text" placeholder="{{ __('Parent or guardian') }}" required>
                            </div>
                            <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Verify relationship') }}</button>
                        </form>
                    </article>

                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Active links') }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-900">{{ __('Guardians') }}</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($guardianLinks as $guardianLink)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $guardianLink->guardian->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $guardianLink->guardian->email }} · {{ str($guardianLink->relationship)->title() }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('schools.learners.guardians.destroy', [$school, $learner, $guardianLink]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">{{ __('Revoke') }}</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No active guardian links.') }}</p>
                            @endforelse
                        </div>
                    </article>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
