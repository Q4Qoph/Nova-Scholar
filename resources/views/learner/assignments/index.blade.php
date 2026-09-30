<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} · {{ __('Assignments') }}</title>
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
            <div class="space-y-2">
                <a class="text-sm font-medium text-indigo-700 hover:underline" href="{{ route('learner.dashboard') }}">← {{ __('Back to your workspace') }}</a>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('Assignments for you') }}</h1>
                <p class="text-sm text-slate-600">{{ __('These assignments were published to your class roster.') }}</p>
            </div>
            <section class="grid gap-4 sm:grid-cols-2">
                @forelse ($assignments as $assignment)
                    <a class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow" href="{{ route('learner.assignments.show', $assignment) }}">
                        <p class="text-sm font-medium text-indigo-700">{{ $assignment->teachingAssignment->subject->name }} · {{ $assignment->teachingAssignment->classGroup->name }}</p>
                        <h2 class="mt-2 text-lg font-semibold">{{ $assignment->title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Due') }} {{ $assignment->due_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                        @php($submission = $assignment->recipients->first()?->submission)
                        @if ($submission?->status === \App\Models\SchoolLearningSubmissionStatus::Submitted->value)
                            <p class="mt-2 text-sm font-medium {{ $submission->is_late ? 'text-amber-700' : 'text-emerald-700' }}">{{ $submission->is_late ? __('Submitted late') : __('Submitted') }}</p>
                        @elseif ($submission?->status === \App\Models\SchoolLearningSubmissionStatus::Draft->value)
                            <p class="mt-2 text-sm font-medium text-indigo-700">{{ __('Draft saved') }}</p>
                        @endif
                        @if ($submission?->status === 'submitted' && $submission->releasedReview?->school_id === $assignment->school_id)
                            <p class="mt-2 font-medium text-indigo-700">{{ __('Feedback available') }}</p>
                        @elseif ($submission?->status !== 'submitted')
                            <p class="mt-2 text-sm text-slate-700">{{ now()->greaterThan($assignment->cutoff_at ?? $assignment->due_at) ? __('Closed') : __('Response needed') }}</p>
                        @endif
                        <p class="mt-4 text-sm font-semibold text-indigo-700">{{ __('View assignment') }} →</p>
                    </a>
                @empty
                    <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">{{ __('No assignments have been published to you yet.') }}</p>
                @endforelse
            </section>
            {{ $assignments->links() }}
        </main>
    </body>
</html>
