<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('Your learning space') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">{{ __('Nova Scholar is ready when you are.') }}</p>
                <h2 class="mt-2 max-w-xl text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('Build a clearer, more consistent study routine.') }}</h2>
                <p class="mt-3 max-w-2xl text-indigo-100">{{ __('Use your documents and AI tutor today. Quizzes, flashcards, and study planning are coming next.') }}</p>
            </section>

            <section class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-indigo-600">{{ __('Your tools') }}</p>
                    <h2 class="mt-1 text-xl font-semibold text-gray-900">{{ __('Continue learning') }}</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'AI Tutor', 'description' => 'Ask questions and get guided explanations.', 'route' => 'chats.index'],
                        ['label' => 'Document Library', 'description' => 'Upload and organize your study materials.', 'route' => 'documents.index'],
                        ['label' => 'Subscription', 'description' => 'View your plan and access status.', 'route' => 'subscription.index'],
                        ['label' => 'Profile', 'description' => 'Manage your account and study preferences.', 'route' => 'profile.edit'],
                    ] as $tool)
                        <a class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md" href="{{ route($tool['route']) }}">
                            <p class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ __($tool['label']) }}</p>
                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ __($tool['description']) }}</p>
                            <span class="mt-4 inline-flex text-sm font-medium text-indigo-600">{{ __('Open') }} →</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Account') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ __('Verified and ready') }}</p>
                    <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('profile.edit') }}">{{ __('Review your profile') }}</a>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Study goal') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ __(':minutes minutes each day', ['minutes' => auth()->user()->learning_preferences['daily_study_goal_minutes'] ?? 60]) }}</p>
                    <p class="mt-4 text-sm text-gray-600">{{ __('You can change this in your profile.') }}</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('Access') }}</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ __('Subscription foundation') }}</p>
                    <a class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('subscription.index') }}">{{ __('View access status') }}</a>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
