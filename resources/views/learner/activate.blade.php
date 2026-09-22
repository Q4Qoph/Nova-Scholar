<x-guest-layout>
    <div class="mb-6 space-y-1">
        <p class="text-sm font-medium text-indigo-600">{{ __('First-time setup') }}</p>
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Activate learner access') }}</h1>
        <p class="text-sm leading-6 text-gray-600">{{ __('Choose a password for your school learner account. This activation link can be used once.') }}</p>
    </div>

    <form method="POST" action="{{ route('learner.activate.store', $token) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <div class="mt-6 flex justify-end">
            <x-primary-button>{{ __('Activate access') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
