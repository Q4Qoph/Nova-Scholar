<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('School workspace') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $school->name }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('invitation_url'))
                <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                    <p class="font-semibold">{{ __('Demo invitation link') }}</p>
                    <p>{{ __('Email delivery is not configured yet. Share this link only with the invited verified user.') }}</p>
                    <a class="break-all font-medium underline" href="{{ session('invitation_url') }}">{{ session('invitation_url') }}</a>
                </div>
            @endif

            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">{{ __('Authorized school context') }}</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">{{ __('Your school workspace is ready.') }}</h2>
                <p class="mt-3 max-w-2xl text-indigo-100">{{ __('This protected overview confirms that your active membership is scoped to this school.') }}</p>
            </section>

            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('School type') }}</p>
                    <p class="mt-2 text-lg font-semibold capitalize text-gray-900">{{ $school->school_type }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Membership') }}</p>
                    <p class="mt-2 text-lg font-semibold capitalize text-gray-900">{{ $membership->status }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Your scoped roles') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ $membership->roles->pluck('role')->map(fn ($role): string => str($role->value)->replace('_', ' ')->title()->toString())->join(', ') }}</p>
                </article>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">{{ __('Registry') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Learners') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('View the school-scoped learner registry.') }}</p>
                    </div>
                    <a class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" href="{{ route('schools.learners.index', $school) }}">{{ __('Open registry') }}</a>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">{{ __('Academic structure') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Years and terms') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Set the school calendar used by future classes and attendance.') }}</p>
                    </div>
                    <a class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" href="{{ route('schools.academic.index', $school) }}">{{ __('Open academic structure') }}</a>
                </div>
            </section>

            @if ($isSchoolAdmin)
                <section class="grid gap-6 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600">{{ __('Memberships') }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Manage school staff') }}</h2>
                        </div>

                        <div class="mt-6 space-y-4">
                            @foreach ($memberships as $schoolMembership)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $schoolMembership->user->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $schoolMembership->user->email }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('schools.memberships.destroy', [$school, $schoolMembership]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">{{ __('Remove') }}</button>
                                        </form>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($schoolMembership->roles as $role)
                                            <form method="POST" action="{{ route('schools.memberships.roles.destroy', [$school, $schoolMembership, $role->role->value]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium capitalize text-indigo-700 hover:bg-indigo-100" type="submit">{{ str($role->role->value)->replace('_', ' ')->toString() }} ×</button>
                                            </form>
                                        @endforeach
                                    </div>
                                    <form class="mt-4 flex flex-wrap items-end gap-3" method="POST" action="{{ route('schools.memberships.roles.store', [$school, $schoolMembership]) }}">
                                        @csrf
                                        <div class="min-w-48 flex-1">
                                            <label class="block text-sm font-medium text-gray-700" for="role-{{ $schoolMembership->id }}">{{ __('Add staff role') }}</label>
                                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="role-{{ $schoolMembership->id }}" name="role">
                                                <option value="teacher">{{ __('Teacher') }}</option>
                                                <option value="bursar">{{ __('Bursar') }}</option>
                                                <option value="school_admin">{{ __('School admin') }}</option>
                                            </select>
                                        </div>
                                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Assign') }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="space-y-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-indigo-600">{{ __('Invitations') }}</p>
                                <h2 class="text-xl font-semibold text-gray-900">{{ __('Invite verified staff') }}</h2>
                            </div>
                            <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.invitations.store', $school) }}">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="invite-email">{{ __('Verified user email') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="invite-email" name="email" type="email" value="{{ old('email') }}" required>
                                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="invite-role">{{ __('Staff role') }}</label>
                                    <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="invite-role" name="role">
                                        <option value="teacher">{{ __('Teacher') }}</option>
                                        <option value="bursar">{{ __('Bursar') }}</option>
                                        <option value="school_admin">{{ __('School admin') }}</option>
                                    </select>
                                    @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Create invitation') }}</button>
                            </form>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('Pending invitations') }}</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($invitations as $invitation)
                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 text-sm">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $invitation->invitee->email }}</p>
                                            <p class="capitalize text-gray-500">{{ str($invitation->role->value)->replace('_', ' ')->toString() }} · {{ __('expires') }} {{ $invitation->expires_at->toFormattedDateString() }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('schools.invitations.destroy', [$school, $invitation]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="font-medium text-red-600 hover:text-red-800" type="submit">{{ __('Revoke') }}</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">{{ __('No pending invitations.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
