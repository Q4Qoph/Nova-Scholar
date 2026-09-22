<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('Academic structure') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $school->name }} {{ __('calendar') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            <a class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('schools.overview', $school) }}">← {{ __('Back to school overview') }}</a>

            @if ($canManage)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('School setup') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Create academic year') }}</h2>
                    </div>
                    <form class="mt-6 grid gap-4 sm:grid-cols-4 sm:items-end" method="POST" action="{{ route('schools.academic-years.store', $school) }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="year-name">{{ __('Name') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="year-name" name="name" type="text" placeholder="2027" required>
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="year-starts">{{ __('Starts') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="year-starts" name="starts_on" type="date" required>
                            @error('starts_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="year-ends">{{ __('Ends') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="year-ends" name="ends_on" type="date" required>
                            @error('ends_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Create year') }}</button>
                    </form>
                </section>
            @endif

            @if ($canManage)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Curriculum setup') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Add subject') }}</h2>
                    </div>
                    <form class="mt-6 grid gap-4 sm:grid-cols-3 sm:items-end" method="POST" action="{{ route('schools.subjects.store', $school) }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="subject-name">{{ __('Subject name') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="subject-name" name="name" type="text" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="subject-code">{{ __('Code') }}</label>
                            <input class="mt-1 block w-full rounded-md border-gray-300 text-sm uppercase shadow-sm" id="subject-code" name="code" type="text" placeholder="MATH" required>
                        </div>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Add subject') }}</button>
                    </form>
                </section>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">{{ __('Subjects') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('School subjects') }}</h2>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">{{ $subjects->count() }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($subjects as $subject)
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ $subject->name }} ({{ $subject->code }})</span>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No subjects have been added yet.') }}</p>
                    @endforelse
                </div>
            </section>

            @if ($canManage)
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600">{{ __('Teaching setup') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Assign teacher') }}</h2>
                    </div>
                    <form class="mt-6 grid gap-4 sm:grid-cols-4 sm:items-end" method="POST" action="{{ route('schools.teaching-assignments.store', $school) }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="assignment-class">{{ __('Class group') }}</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="assignment-class" name="class_group_id" required>
                                <option value="">{{ __('Select class') }}</option>
                                @foreach ($academicYears as $academicYear)
                                    @foreach ($academicYear->classGroups as $classGroup)
                                        <option value="{{ $classGroup->id }}">{{ $academicYear->name }} · {{ $classGroup->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="assignment-subject">{{ __('Subject') }}</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="assignment-subject" name="subject_id" required>
                                <option value="">{{ __('Select subject') }}</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="assignment-teacher">{{ __('Teacher') }}</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="assignment-teacher" name="teacher_user_id" required>
                                <option value="">{{ __('Select teacher') }}</option>
                                @foreach ($teachers as $teacherMembership)
                                    <option value="{{ $teacherMembership->user_id }}">{{ $teacherMembership->user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Assign teacher') }}</button>
                    </form>
                </section>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">{{ __('Teaching assignments') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Current assignments') }}</h2>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">{{ $assignments->count() }}</span>
                </div>
                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($assignments as $assignment)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium text-gray-900">{{ $assignment->classGroup->name }} · {{ $assignment->subject->name }}</p>
                                <p class="text-sm text-gray-500">{{ $assignment->teacher->name }}</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ __('Active') }}</span>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-gray-500">{{ __('No teaching assignments have been added yet.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-6">
                @forelse ($academicYears as $academicYear)
                    <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-indigo-600">{{ __('Academic year') }}</p>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $academicYear->name }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $academicYear->starts_on->toFormattedDateString() }} – {{ $academicYear->ends_on->toFormattedDateString() }}</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium capitalize text-emerald-700">{{ $academicYear->status }}</span>
                        </div>

                        @if ($canManage)
                            <form class="mt-6 grid gap-4 border-t border-gray-100 pt-6 sm:grid-cols-4 sm:items-end" method="POST" action="{{ route('schools.terms.store', [$school, $academicYear]) }}">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="term-name-{{ $academicYear->id }}">{{ __('Term name') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="term-name-{{ $academicYear->id }}" name="name" type="text" placeholder="Term 1" required>
                                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="term-starts-{{ $academicYear->id }}">{{ __('Starts') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="term-starts-{{ $academicYear->id }}" name="starts_on" type="date" required>
                                    @error('starts_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="term-ends-{{ $academicYear->id }}">{{ __('Ends') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="term-ends-{{ $academicYear->id }}" name="ends_on" type="date" required>
                                    @error('ends_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Add term') }}</button>
                            </form>

                            <form class="mt-6 grid gap-4 border-t border-gray-100 pt-6 sm:grid-cols-4 sm:items-end" method="POST" action="{{ route('schools.class-groups.store', [$school, $academicYear]) }}">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="class-name-{{ $academicYear->id }}">{{ __('Class name') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="class-name-{{ $academicYear->id }}" name="name" type="text" placeholder="Grade 5 A" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="grade-level-{{ $academicYear->id }}">{{ __('Grade level') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="grade-level-{{ $academicYear->id }}" name="grade_level" type="text" placeholder="Grade 5" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="stream-{{ $academicYear->id }}">{{ __('Stream') }}</label>
                                    <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="stream-{{ $academicYear->id }}" name="stream" type="text" placeholder="A">
                                </div>
                                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Add class') }}</button>
                            </form>
                        @endif

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            @forelse ($academicYear->terms as $term)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <p class="font-semibold text-gray-900">{{ $term->name }}</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $term->starts_on->toFormattedDateString() }} – {{ $term->ends_on->toFormattedDateString() }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No terms have been added yet.') }}</p>
                            @endforelse
                        </div>
                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <p class="text-sm font-medium text-gray-600">{{ __('Class groups') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($academicYear->classGroups as $classGroup)
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-sm text-violet-700">{{ $classGroup->name }} · {{ $classGroup->grade_level }}{{ $classGroup->stream ? ' · '.$classGroup->stream : '' }}</span>
                                @empty
                                    <p class="text-sm text-gray-500">{{ __('No class groups have been added yet.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">{{ __('No academic years have been created yet.') }}</div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
