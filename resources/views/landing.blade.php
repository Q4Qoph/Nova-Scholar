<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('Nova Scholar helps schools organise learners, teaching and family visibility in one focused workspace.') }}">
        <title>{{ config('app.name') }} · {{ __('School administration and learning') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 text-white antialiased">
        <main class="mx-auto flex min-h-screen max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
            <header class="flex items-center justify-between gap-6">
                <a class="flex items-center gap-3 text-lg font-semibold tracking-tight" href="{{ route('home') }}">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-500 text-sm font-bold shadow-lg shadow-indigo-950/40">NS</span>
                    <span>{{ config('app.name') }}</span>
                </a>
                <nav class="flex items-center gap-2 text-sm font-medium sm:gap-4">
                    <a class="hidden text-slate-300 transition hover:text-white sm:inline" href="#workspaces">{{ __('Workspaces') }}</a>
                    <a class="hidden text-slate-300 transition hover:text-white sm:inline" href="{{ url('/school/login') }}">{{ __('School sign in') }}</a>
                    @auth
                        <a class="rounded-lg bg-white px-4 py-2 text-slate-950 transition hover:bg-indigo-100" href="{{ route('dashboard') }}">{{ __('Open workspace') }}</a>
                    @else
                        <a class="rounded-lg px-3 py-2 text-slate-300 transition hover:text-white" href="{{ route('login') }}">{{ __('Log in') }}</a>
                        <a class="rounded-lg bg-white px-4 py-2 text-slate-950 transition hover:bg-indigo-100" href="{{ route('register') }}">{{ __('Create account') }}</a>
                    @endauth
                </nav>
            </header>

            <section class="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[1.05fr_.95fr] lg:py-24">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-indigo-300">{{ __('School administration · teaching · learning') }}</p>
                    <h1 class="mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-6xl">{{ __('A clearer school day, from learner records to learning.') }}</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">{{ __('Nova Scholar gives Kenyan day, boarding, and mixed schools one focused workspace for staff, learners, and verified guardians.') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a class="rounded-lg bg-indigo-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-950/40 transition hover:bg-indigo-400" href="{{ route('register') }}">{{ __('Build your school workspace') }}</a>
                        @if (app()->environment('local'))
                            <a class="rounded-lg border border-indigo-300/40 px-5 py-3 text-sm font-semibold text-indigo-100 transition hover:border-indigo-200 hover:bg-indigo-500/10" href="{{ url('/school/login') }}">{{ __('Open local demo') }}</a>
                        @endif
                        <a class="rounded-lg border border-slate-700 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900" href="#workspaces">{{ __('See what is included') }}</a>
                    </div>
                    <p class="mt-5 text-sm text-slate-400">{{ __('Start with a protected foundation. Add operational workflows as your school is ready.') }}</p>
                </div>

                <div class="relative">
                    <div class="absolute -inset-8 rounded-[2.5rem] bg-indigo-500/10 blur-3xl"></div>
                    <div class="relative rounded-3xl border border-white/10 bg-white/[0.07] p-4 shadow-2xl shadow-black/30 backdrop-blur sm:p-6">
                        <div class="rounded-2xl bg-white p-5 text-slate-900 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('School workspace') }}</p>
                                    <h2 class="mt-2 text-xl font-semibold">{{ __('Keep the whole picture in view.') }}</h2>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('Protected') }}</span>
                            </div>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach ([['label' => __('Learner registry'), 'detail' => __('Admissions and enrolments')], ['label' => __('Academic structure'), 'detail' => __('Years, terms, classes, subjects')], ['label' => __('Teaching assignments'), 'detail' => __('Connect teachers to learning')], ['label' => __('Guardian visibility'), 'detail' => __('Verified learner relationships')]] as $item)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $item['detail'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-5 flex items-center justify-between rounded-xl bg-indigo-50 px-4 py-3 text-sm">
                                <span class="font-medium text-indigo-950">{{ __('One school context') }}</span>
                                <span class="text-indigo-600">{{ __('Scoped access →') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workspaces" class="border-t border-white/10 py-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-300">{{ __('Choose your space') }}</p>
                    <h2 class="mt-2 text-2xl font-semibold">{{ __('One front door, clear paths inside.') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-400">{{ __('School operations, family visibility, learner access, and independent study use separate protected workspaces.') }}</p>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <a class="group rounded-2xl border border-indigo-300/30 bg-indigo-500/10 p-5 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-500/20" href="{{ url('/school/login') }}">
                        <p class="text-sm font-semibold text-indigo-300">{{ __('School teams') }}</p>
                        <h3 class="mt-2 font-semibold">{{ __('School workspace') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Learners, academics, staff, imports, and school finance.') }}</p>
                        <span class="mt-4 inline-flex text-sm font-medium text-indigo-200">{{ __('School sign in') }} →</span>
                    </a>
                    <a class="group rounded-2xl border border-white/10 bg-white/[0.05] p-5 transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/[0.08]" href="{{ url('/platform/login') }}">
                        <p class="text-sm font-semibold text-indigo-300">{{ __('Nova operations') }}</p>
                        <h3 class="mt-2 font-semibold">{{ __('Platform workspace') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Provision and oversee school workspaces.') }}</p>
                        <span class="mt-4 inline-flex text-sm font-medium text-indigo-200">{{ __('Platform sign in') }} →</span>
                    </a>
                    <a class="group rounded-2xl border border-white/10 bg-white/[0.05] p-5 transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/[0.08]" href="{{ url('/learner/login') }}">
                        <p class="text-sm font-semibold text-indigo-300">{{ __('Learners') }}</p>
                        <h3 class="mt-2 font-semibold">{{ __('Learner sign in') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Use a school-issued learner login and password.') }}</p>
                        <span class="mt-4 inline-flex text-sm font-medium text-indigo-200">{{ __('Learner sign in') }} →</span>
                    </a>
                    <a class="group rounded-2xl border border-white/10 bg-white/[0.05] p-5 transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/[0.08]" href="{{ route('login') }}">
                        <p class="text-sm font-semibold text-indigo-300">{{ __('Independent study') }}</p>
                        <h3 class="mt-2 font-semibold">{{ __('Personal workspace') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ __('Documents, practice, subscription, and profile tools.') }}</p>
                        <span class="mt-4 inline-flex text-sm font-medium text-indigo-200">{{ __('Account sign in') }} →</span>
                    </a>
                </div>
            </section>

            <footer class="flex flex-col gap-2 border-t border-white/10 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ __('Nova Scholar · school administration and e-learning') }}</span>
                <span>{{ __('AI features are intentionally deferred from the school release.') }}</span>
            </footer>
        </main>
    </body>
</html>
