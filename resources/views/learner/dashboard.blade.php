<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} · {{ __('Learner workspace') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <div>
                    <p class="text-sm font-semibold text-indigo-600">{{ config('app.name') }}</p>
                    <p class="text-xs text-slate-500">{{ __('Learner workspace') }}</p>
                </div>
                <form method="POST" action="{{ route('learner.logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:text-indigo-700" type="submit">{{ __('Sign out') }}</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-5xl space-y-6 px-5 py-10 sm:px-8">
            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">{{ __('Welcome') }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $learnerProfile->preferred_name ?: $learnerProfile->first_name }}</h1>
                <p class="mt-3 max-w-2xl text-indigo-100">{{ __('This restricted workspace shows only your school learning context.') }}</p>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-indigo-600">{{ __('Your classes') }}</p>
                    <h2 class="text-xl font-semibold text-slate-900">{{ __('Current and previous placements') }}</h2>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @forelse ($learnerProfile->enrolments->flatMap->classMemberships as $placement)
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-semibold text-slate-900">{{ $placement->classGroup->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $placement->classGroup->academicYear->name }} · {{ $placement->starts_on->toFormattedDateString() }} – {{ $placement->ends_on?->toFormattedDateString() ?? __('Current') }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Your school has not assigned a class yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </main>
    </body>
</html>
