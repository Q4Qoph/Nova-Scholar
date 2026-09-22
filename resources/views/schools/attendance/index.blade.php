<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('Attendance') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $school->name }} {{ __('registers') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            <a class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('schools.overview', $school) }}">← {{ __('Back to school overview') }}</a>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-indigo-600">{{ __('New register') }}</p>
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('Open a class attendance session') }}</h2>
                </div>
                <form class="mt-6 grid gap-4 sm:grid-cols-3 sm:items-end" method="POST" action="{{ route('schools.attendance.store', $school) }}">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="attendance-assignment">{{ __('Class and subject') }}</label>
                        <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="attendance-assignment" name="teaching_assignment_id" onchange="this.form.class_group_id.value = this.options[this.selectedIndex].dataset.classGroupId || ''" required>
                            <option value="">{{ __('Select assignment') }}</option>
                            @foreach ($assignments as $assignment)
                                <option data-class-group-id="{{ $assignment->class_group_id }}" value="{{ $assignment->id }}">{{ $assignment->classGroup->name }} · {{ $assignment->subject->name }}</option>
                            @endforeach
                        </select>
                        @error('teaching_assignment_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <input name="class_group_id" type="hidden" value="{{ old('class_group_id') }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="attendance-date">{{ __('Session date') }}</label>
                        <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="attendance-date" name="session_date" type="date" value="{{ old('session_date', now()->toDateString()) }}" required>
                        @error('session_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-sm text-gray-500 sm:col-span-3">{{ __('Learners without a submitted status remain unmarked; they are not treated as absent.') }}</p>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Open register') }}</button>
                </form>
            </section>

            @if ($selectedSession)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-indigo-600">{{ $selectedSession->teachingAssignment->subject->name }}</p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $selectedSession->classGroup->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $selectedSession->session_date->toFormattedDateString() }} · {{ __('Version') }} {{ $selectedSession->version }}</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700">{{ __('Open') }}</span>
                    </div>
                    <form class="mt-6 space-y-4" method="POST" action="{{ route('schools.attendance.store', $school) }}">
                        @csrf
                        <input name="attendance_session_id" type="hidden" value="{{ $selectedSession->id }}">
                        <input name="version" type="hidden" value="{{ $selectedSession->version }}">
                        <input name="class_group_id" type="hidden" value="{{ $selectedSession->class_group_id }}">
                        <input name="teaching_assignment_id" type="hidden" value="{{ $selectedSession->teaching_assignment_id }}">
                        <input name="session_date" type="hidden" value="{{ $selectedSession->session_date->toDateString() }}">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead><tr><th class="px-3 py-3 font-medium text-gray-500">{{ __('Learner') }}</th><th class="px-3 py-3 font-medium text-gray-500">{{ __('Status') }}</th></tr></thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($selectedSession->entries as $entry)
                                        <tr>
                                            <td class="px-3 py-3 font-medium text-gray-900">{{ $entry->enrolment->learnerProfile->preferred_name ?: $entry->enrolment->learnerProfile->first_name }} {{ $entry->enrolment->learnerProfile->last_name }}</td>
                                            <td class="px-3 py-3">
                                                <select class="rounded-md border-gray-300 text-sm shadow-sm" name="entries[{{ $loop->index }}][status]">
                                                    @foreach (['unmarked', 'present', 'absent', 'late', 'excused'] as $status)
                                                        <option value="{{ $status }}" @selected($entry->status === $status)>{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm" name="entries[{{ $loop->index }}][correction_reason]" type="text" placeholder="{{ __('Reason if changing status') }}">
                                                <input name="entries[{{ $loop->index }}][enrolment_id]" type="hidden" value="{{ $entry->enrolment_id }}">
                                                @error('entries.'.$entry->enrolment_id.'.correction_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @error('version')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('entries')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Save register') }}</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
