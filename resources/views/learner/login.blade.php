<x-guest-layout>
    <div class="mb-6 space-y-1">
        <p class="text-sm font-medium text-indigo-600">{{ __('Managed learner access') }}</p>
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Learner sign in') }}</h1>
        <p class="text-sm leading-6 text-gray-600">{{ __('Use the login ID provided by your school.') }}</p>
    </div>

    <form method="POST" action="{{ route('learner.login') }}">
        @csrf
        <div>
            <x-input-label for="learner_login_id" :value="__('Learner login ID')" />
            <x-text-input id="learner_login_id" class="mt-1 block w-full" type="text" name="learner_login_id" :value="old('learner_login_id')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('learner_login_id')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-6 flex items-center justify-between gap-3">
            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('login') }}">{{ __('Adult sign in') }}</a>
            <x-primary-button>{{ __('Sign in') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
