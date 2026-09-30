<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} · {{ $assignment->title }}</title>
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
        <x-draft-guard :saved-text="$recipient->submission?->response_text ?? ''" class="mx-auto max-w-3xl space-y-6 px-5 py-8 sm:px-8 sm:py-10">
            <div class="space-y-2">
                <a class="text-sm font-medium text-indigo-700 hover:underline" href="{{ route('learner.assignments.index') }}">← {{ __('All assignments') }}</a>
                <p class="pt-2 text-sm font-medium text-indigo-700">{{ $assignment->teachingAssignment->subject->name }} · {{ $assignment->teachingAssignment->classGroup->name }}</p>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $assignment->title }}</h1>
                <p class="text-sm text-slate-600">{{ __('Due') }} {{ $assignment->due_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                @if ($assignment->cutoff_at !== null)
                    <p class="text-sm text-slate-600">{{ __('Submission cutoff') }} {{ $assignment->cutoff_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                @endif
            </div>
            @if (session('status'))
                <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif
            <article class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <section>
                    <h2 class="font-semibold">{{ __('Instructions') }}</h2>
                    <div class="mt-2 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $assignment->instructions }}</div>
                </section>
                <section class="border-t border-slate-200 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lesson') }}</p>
                    <h2 class="mt-2 text-lg font-semibold">{{ $assignment->sourceLessonVersion->title }}</h2>
                    <div class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $assignment->sourceLessonVersion->body }}</div>
                </section>
                <section class="border-t border-slate-200 pt-5">
                    <h2 class="font-semibold">{{ __('Your response') }}</h2>
                    @if ($recipient->submission?->status === \App\Models\SchoolLearningSubmissionStatus::Submitted->value)
                        <p class="mt-2 rounded-lg {{ $recipient->submission->is_late ? 'bg-amber-50 text-amber-900' : 'bg-emerald-50 text-emerald-900' }} p-3 text-sm" role="status">
                            {{ $recipient->submission->is_late ? __('Submitted late') : __('Submitted') }} · {{ __('Reference') }} {{ $recipient->submission->acknowledgement_reference }}
                        </p>
                        <p class="mt-2 text-sm text-slate-600">{{ __('Submitted') }} {{ $recipient->submission->submitted_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                        <div class="mt-4 whitespace-pre-wrap break-words rounded-lg bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $recipient->submission->response_text }}</div>
                        @if ($releasedReview = $recipient->submission->releasedReview)
                            <section class="mt-6 rounded-lg border border-indigo-200 p-4" aria-label="Teacher feedback">
                                <h3 class="font-semibold text-slate-900">{{ __('Teacher feedback') }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ __('Released') }} {{ $releasedReview->released_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                                <div class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $releasedReview->feedback }}</div>
                                @if ($releasedReview->score !== null)
                                    <p class="mt-3 font-semibold text-indigo-700">{{ __('Score') }}: {{ $releasedReview->score }} / {{ $releasedReview->maximum_score }}</p>
                                @else
                                    <p class="mt-3 text-sm text-slate-600">{{ __('Feedback only · no score') }}</p>
                                @endif
                            </section>
                        @else
                            <p class="mt-4 text-sm text-slate-600" role="status">{{ __('Awaiting teacher feedback') }}</p>
                        @endif
                    @else
                        @if ($recipient->submission?->status === \App\Models\SchoolLearningSubmissionStatus::Draft->value)
                            <p class="mt-2 text-sm text-indigo-700">{{ __('Draft saved') }} · {{ $recipient->submission->draft_saved_at->timezone($assignment->school->timezone)->format('d M Y, H:i') }}</p>
                        @endif
                        <form @submit="if (!$event.submitter?.hasAttribute('formaction') && !confirm('Submit your final response? You cannot change it after submission.')) { $event.preventDefault(); } else { submitting = true; }" class="mt-4 space-y-3" method="POST" action="{{ route('learner.assignments.submit', $assignment) }}">
                            @csrf
                            <label class="grid gap-1 text-sm font-medium text-slate-700">
                                <span>{{ __('Write your response') }}</span>
                                <textarea class="min-h-40 rounded-lg border-slate-300 text-sm" maxlength="10000" name="response_text" required>{{ old('response_text', $recipient->submission?->response_text ?? '') }}</textarea>
                            </label>
                            @error('response_text') <span class="text-sm text-rose-700">{{ $message }}</span> @enderror
                            <p class="text-sm text-slate-600">{{ __('Save a draft before leaving. Final submission cannot be changed.') }}</p>
                            <div class="flex flex-wrap gap-3">
                                <button class="rounded-lg border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50" formaction="{{ route('learner.assignments.draft', $assignment) }}" type="submit">{{ __('Save draft') }}</button>
                                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">{{ __('Submit final response') }}</button>
                            </div>
                        </form>
                    @endif
                </section>
            </article>
        </x-draft-guard>
    </body>
</html>
