<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} · {{ __('Lessons') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <div>
                    <a class="text-sm font-semibold text-indigo-600" href="{{ route('learner.dashboard') }}">{{ config('app.name') }}</a>
                    <p class="text-xs text-slate-500">{{ __('School learning') }}</p>
                </div>
                <form method="POST" action="{{ route('learner.logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:text-indigo-700" type="submit">{{ __('Sign out') }}</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-5xl space-y-6 px-5 py-8 sm:px-8 sm:py-10">
            <div>
                <a class="text-sm font-medium text-indigo-700 hover:underline" href="{{ route('learner.dashboard') }}">← {{ __('Back to your workspace') }}</a>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('Lessons for your current classes') }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ __('Your school publishes these materials for your active class placements.') }}</p>
            </div>
            <section class="grid gap-4 sm:grid-cols-2">
                @forelse ($courses as $course)
                    <a class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow" href="{{ route('learner.courses.show', $course) }}">
                        <p class="text-sm font-medium text-indigo-700">{{ $course->teachingAssignment->subject->name }}</p>
                        <h2 class="mt-2 text-lg font-semibold">{{ $course->title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $course->teachingAssignment->classGroup->name }}</p>
                        <p class="mt-4 text-sm font-semibold text-indigo-700">{{ __('View lessons') }} →</p>
                    </a>
                @empty
                    <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">{{ __('No published lessons are available for your current class placements yet.') }}</p>
                @endforelse
            </section>
        </main>
    </body>
</html>
