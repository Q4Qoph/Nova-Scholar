<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <main class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8 sm:px-10">
            <header class="flex items-center justify-between gap-4">
                <a class="text-lg font-semibold tracking-tight text-indigo-700" href="{{ route('home') }}">{{ config('app.name') }}</a>
                <nav class="flex items-center gap-3 text-sm font-medium">
                    @auth
                        <a class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" href="{{ route('dashboard') }}">{{ __('Open dashboard') }}</a>
                    @else
                        <a class="rounded-md px-4 py-2 text-slate-700 hover:text-indigo-700" href="{{ route('login') }}">{{ __('Log in') }}</a>
                        <a class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" href="{{ route('register') }}">{{ __('Create account') }}</a>
                    @endauth
                </nav>
            </header>

            <section class="flex flex-1 flex-col justify-center py-16 sm:py-24">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">{{ __('Study with clarity') }}</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-6xl">{{ __('Your notes, questions, and study goals in one focused place.') }}</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">{{ __('Nova Scholar is an AI-powered study companion for students who want to understand more, organize better, and keep moving forward.') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700" href="{{ route('register') }}">{{ __('Start learning') }}</a>
                    <a class="rounded-md border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700" href="{{ route('login') }}">{{ __('I already have an account') }}</a>
                </div>
            </section>

            <section class="grid gap-4 pb-8 sm:grid-cols-3">
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">{{ __('Learn from your materials') }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Bring your notes together when the document library launches.') }}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">{{ __('Ask better questions') }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Use an AI tutor built around your learning context.') }}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">{{ __('Build the habit') }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Turn your goals into a consistent study routine.') }}</p></article>
            </section>
        </main>
    </body>
</html>
