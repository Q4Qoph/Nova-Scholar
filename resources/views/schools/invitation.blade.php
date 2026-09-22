<x-guest-layout>
    <div class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-12 sm:px-6">
        <div class="w-full space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="space-y-2">
                <p class="text-sm font-medium text-indigo-600">{{ __('School invitation') }}</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Join :school', ['school' => $invitation->school->name]) }}</h1>
                <p class="text-sm leading-6 text-gray-600">{{ __('You have been invited as a :role. Accepting will create an active school membership.', ['role' => str($invitation->role->value)->replace('_', ' ')->toString()]) }}</p>
            </div>

            <form method="POST" action="{{ route('school-invitations.accept', $token) }}">
                @csrf
                <button class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Accept invitation') }}</button>
            </form>
        </div>
    </div>
</x-guest-layout>
