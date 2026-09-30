<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('Guardian portal') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Linked learners') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">{{ __('Verified relationships') }}</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">{{ __('Your linked learners') }}</h2>
                <p class="mt-3 max-w-2xl text-indigo-100">{{ __('Only active relationships verified by a school administrator appear here.') }}</p>
            </section>

            <section class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white px-6 shadow-sm">
                @forelse ($links as $link)
                    <article class="flex flex-wrap items-center justify-between gap-4 py-5">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $link->enrolment->learnerProfile->preferred_name ?: $link->enrolment->learnerProfile->first_name }} {{ $link->enrolment->learnerProfile->last_name }}</p>
                            <p class="text-sm text-gray-500">{{ $link->school->name }} · {{ __('Admission') }}: {{ $link->enrolment->admission_number }} · {{ __(':relationship', ['relationship' => str($link->relationship)->title()]) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('guardian.learners.index', ['learner' => $link->enrolment_id]) }}">{{ __('View attendance') }}</a>
                            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('guardian.learners.statements.show', $link->enrolment) }}">{{ __('Fee statement') }}</a>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">{{ __('Verified') }}</span>
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-sm text-gray-500">{{ __('No active learner relationships are available.') }}</p>
                @endforelse
            </section>

            @if ($selectedLink)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ $selectedLink->school->name }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $selectedLink->enrolment->learnerProfile->preferred_name ?: $selectedLink->enrolment->learnerProfile->first_name }} {{ $selectedLink->enrolment->learnerProfile->last_name }} · {{ __('Recent attendance') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('Only attendance linked to this verified relationship is shown.') }}</p>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-3 font-medium text-gray-500">{{ __('Date') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-500">{{ __('Class') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-500">{{ __('Subject') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-500">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($attendanceSessions as $attendanceSession)
                                    @foreach ($attendanceSession->entries as $entry)
                                        <tr>
                                            <td class="px-3 py-3 text-gray-700">{{ $attendanceSession->session_date->toFormattedDateString() }}</td>
                                            <td class="px-3 py-3 text-gray-700">{{ $attendanceSession->classGroup->name }}</td>
                                            <td class="px-3 py-3 text-gray-700">{{ $attendanceSession->teachingAssignment->subject->name }}</td>
                                            <td class="px-3 py-3 font-medium capitalize text-gray-900">{{ $entry->status }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr><td class="px-3 py-4 text-sm text-gray-500" colspan="4">{{ __('No attendance records are available yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-indigo-600">{{ __('Communications') }}</p>
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('Recent school notices') }}</h2>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($notices as $notice)
                        <article class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $notice->announcement->title }}</h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $notice->announcement->school->name }} · {{ $notice->delivered_at?->toFormattedDateString() }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ __('Delivered') }}</span>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $notice->announcement->body }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No school notices are available.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
