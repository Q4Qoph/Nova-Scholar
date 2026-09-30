<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} · {{ $course->title }}</title>
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
                <a class="text-sm font-medium text-indigo-700 hover:underline" href="{{ route('learner.courses.index') }}">← {{ __('All lessons') }}</a>
                <p class="mt-3 text-sm font-medium text-indigo-700">{{ $course->teachingAssignment->subject->name }} · {{ $course->teachingAssignment->classGroup->name }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $course->title }}</h1>
            </div>
            <section class="space-y-4">
                @php($publishedLessons = $course->lessons->filter(fn ($lesson) => $lesson->versions->isNotEmpty()))
                @forelse ($publishedLessons as $lesson)
                    @foreach ($lesson->versions as $version)
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lesson') }} {{ $lesson->position }}</p>
                            <h2 class="mt-2 text-xl font-semibold">{{ $version->title }}</h2>
                            <div class="mt-4 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $version->body }}</div>
                            @if ($version->resources->isNotEmpty())
                                <div class="mt-6 border-t border-slate-200 pt-4">
                                    <h3 class="font-semibold">{{ __('Lesson resources') }}</h3>
                                    <ul class="mt-3 space-y-2">
                                        @foreach ($version->resources as $resource)
                                            <li>
                                                <a class="inline-flex items-center gap-2 text-sm font-medium text-indigo-700 hover:underline" href="{{ route('learner.lesson-resources.download', $resource) }}">
                                                    <span>{{ $resource->display_name }}</span>
                                                    <span aria-hidden="true">↓</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach
                @empty
                    <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">{{ __('This course has no published lessons yet.') }}</p>
                @endforelse
            </section>
        </main>
    </body>
</html>
