<x-filament-panels::page>
    @php($school = $this->getSchool())

    <div class="grid gap-6">
        <section class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-1">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $school->name }}</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Academic structure</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Review the school calendar, classes, subjects, and teaching assignments within this tenant.</p>
            </div>
        </section>

        @if ($this->canManageAcademicYears())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">School setup</p>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Create academic year</h2>
                </div>
                <form class="mt-5 grid gap-4 lg:grid-cols-4 lg:items-end" wire:submit="createAcademicYear">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="academic-year-name">Name</label>
                        <input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="academic-year-name" wire:model="academicYearName" type="text" placeholder="2027" required>
                        @error('academicYearName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="academic-year-starts">Starts</label>
                        <input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="academic-year-starts" wire:model="academicYearStartsOn" type="date" required>
                        @error('academicYearStartsOn')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="academic-year-ends">Ends</label>
                        <input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="academic-year-ends" wire:model="academicYearEndsOn" type="date" required>
                        @error('academicYearEndsOn')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Create year</button>
                </form>
            </section>
        @endif

        @if ($this->canManageSubjects())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Curriculum setup</p>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Add subject</h2>
                </div>
                <form class="mt-5 grid gap-4 sm:grid-cols-3 sm:items-end" wire:submit="createSubject">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="subject-name">Subject name</label>
                        <input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="subject-name" wire:model="subjectName" type="text" required>
                        @error('subjectName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="subject-code">Code</label>
                        <input class="mt-1 block w-full rounded-lg border-gray-300 text-sm uppercase shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="subject-code" wire:model="subjectCode" type="text" placeholder="MATH" required>
                        @error('subjectCode')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Add subject</button>
                </form>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10"><h3 class="font-semibold text-gray-950 dark:text-white">Academic years and classes</h3></div>
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($this->getAcademicYears() as $academicYear)
                            <div class="px-6 py-4"><div class="flex flex-wrap items-center justify-between gap-3"><p class="font-medium text-gray-950 dark:text-white">{{ $academicYear->name }}</p><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium capitalize text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">{{ $academicYear->status }}</span></div><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $academicYear->starts_on->toFormattedDateString() }} – {{ $academicYear->ends_on->toFormattedDateString() }}</p>
                                @if ($this->canManageTerms())
                                    <form class="mt-4 grid gap-3 border-t border-gray-200 pt-4 dark:border-white/10 sm:grid-cols-4 sm:items-end" wire:submit="createTerm({{ $academicYear->id }})">
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="term-name-{{ $academicYear->id }}">Term name</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="term-name-{{ $academicYear->id }}" wire:model="termNames.{{ $academicYear->id }}" type="text" placeholder="Term 1" required>@error("termNames.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="term-starts-{{ $academicYear->id }}">Starts</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="term-starts-{{ $academicYear->id }}" wire:model="termStartsOn.{{ $academicYear->id }}" type="date" required>@error("termStartsOn.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="term-ends-{{ $academicYear->id }}">Ends</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="term-ends-{{ $academicYear->id }}" wire:model="termEndsOn.{{ $academicYear->id }}" type="date" required>@error("termEndsOn.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Add term</button>
                                    </form>
                                @endif
                                @if ($this->canManageClassGroups())
                                    <form class="mt-4 grid gap-3 border-t border-gray-200 pt-4 dark:border-white/10 sm:grid-cols-4 sm:items-end" wire:submit="createClassGroup({{ $academicYear->id }})">
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="class-name-{{ $academicYear->id }}">Class name</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="class-name-{{ $academicYear->id }}" wire:model="classNames.{{ $academicYear->id }}" type="text" placeholder="Grade 5 A" required>@error("classNames.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="class-grade-{{ $academicYear->id }}">Grade level</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="class-grade-{{ $academicYear->id }}" wire:model="classGradeLevels.{{ $academicYear->id }}" type="text" placeholder="Grade 5" required>@error("classGradeLevels.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <div><label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="class-stream-{{ $academicYear->id }}">Stream</label><input class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="class-stream-{{ $academicYear->id }}" wire:model="classStreams.{{ $academicYear->id }}" type="text" placeholder="A">@error("classStreams.{$academicYear->id}")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</div>
                                        <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Add class</button>
                                    </form>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-2">@forelse ($academicYear->classGroups as $classGroup)<span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $classGroup->name }}</span>@empty<span class="text-sm text-gray-500 dark:text-gray-400">No classes yet.</span>@endforelse</div></div>
                    @empty
                        <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No academic years found.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10"><h3 class="font-semibold text-gray-950 dark:text-white">Subjects</h3></div>
                <div class="flex flex-wrap gap-2 p-6">
                    @forelse ($this->getSubjects() as $subject)<span class="rounded-full bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">{{ $subject->name }} ({{ $subject->code }})</span>@empty<p class="text-sm text-gray-500 dark:text-gray-400">No subjects found.</p>@endforelse
                </div>
            </section>
        </div>

        @if ($this->canManageTeachingAssignments())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Teaching setup</p>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Assign teacher</h2>
                </div>
                <form class="mt-5 grid gap-4 lg:grid-cols-4 lg:items-end" wire:submit="createTeachingAssignment">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="assignment-class">Class group</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="assignment-class" wire:model="assignmentClassGroupId" required>
                            <option value="">Select class</option>
                            @foreach ($this->getAssignableClassGroups() as $classGroup)
                                <option value="{{ $classGroup->id }}">{{ $classGroup->academicYear->name }} · {{ $classGroup->name }}</option>
                            @endforeach
                        </select>
                        @error('assignmentClassGroupId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="assignment-subject">Subject</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="assignment-subject" wire:model="assignmentSubjectId" required>
                            <option value="">Select subject</option>
                            @foreach ($this->getAssignableSubjects() as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                            @endforeach
                        </select>
                        @error('assignmentSubjectId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="assignment-teacher">Teacher</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="assignment-teacher" wire:model="assignmentTeacherUserId" required>
                            <option value="">Select teacher</option>
                            @foreach ($this->getAssignableTeachers() as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->email }})</option>
                            @endforeach
                        </select>
                        @error('assignmentTeacherUserId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Assign teacher</button>
                </form>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10"><h3 class="font-semibold text-gray-950 dark:text-white">Teaching assignments</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($this->getAssignments() as $assignment)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4"><div><p class="font-medium text-gray-950 dark:text-white">{{ $assignment->classGroup->name }} · {{ $assignment->subject->name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $assignment->teacher->name }}</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">Active</span></div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No teaching assignments found.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
