<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @php
        $subjects = old('preferred_subjects', $user->learning_preferences['preferred_subjects'] ?? []);
        $subjectList = is_array($subjects) ? implode(', ', $subjects) : $subjects;
    @endphp

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="photo" :value="__('Profile photo')" />

            @if ($user->profile_photo_path !== null)
                <img src="{{ route('profile.photo') }}" alt="{{ __('Current profile photo') }}" class="mt-3 h-16 w-16 rounded-full object-cover" />
            @endif

            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-gray-700" />
            <p class="mt-1 text-sm text-gray-600">{{ __('JPEG, PNG, or WebP up to 2 MB.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        <div>
            <x-input-label for="preferred_subjects" :value="__('Subjects you are studying')" />
            <x-text-input id="preferred_subjects" name="preferred_subjects" type="text" class="mt-1 block w-full" :value="$subjectList" autocomplete="off" />
            <p class="mt-1 text-sm text-gray-600">{{ __('Separate subjects with commas.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('preferred_subjects')" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="daily_study_goal_minutes" :value="__('Daily study goal (minutes)')" />
                <x-text-input id="daily_study_goal_minutes" name="daily_study_goal_minutes" type="number" min="0" max="720" class="mt-1 block w-full" :value="old('daily_study_goal_minutes', $user->learning_preferences['daily_study_goal_minutes'] ?? 60)" />
                <x-input-error class="mt-2" :messages="$errors->get('daily_study_goal_minutes')" />
            </div>

            <div>
                <x-input-label for="timezone" :value="__('Timezone')" />
                <x-text-input id="timezone" name="timezone" type="text" class="mt-1 block w-full" :value="old('timezone', $user->learning_preferences['timezone'] ?? 'Africa/Nairobi')" required />
                <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
