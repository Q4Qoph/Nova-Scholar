<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($membership = $this->getCurrentMembership())

    <div class="grid gap-6">
        <section class="rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
            <p class="text-sm font-medium text-indigo-100">{{ $school->name }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Keep school operations and learning connected.</h2>
            <p class="mt-3 max-w-2xl text-indigo-100">Choose a task below to continue your school day.</p>
        </section>



        @if ($this->canManageStaff())
            <section class="rounded-xl border border-gray-200 bg-white p-5 dark:bg-gray-900" aria-label="School demo setup">
                <h2 class="text-lg font-semibold">School demo setup</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Set up one class for your demonstration, then repeat the steps for each class when ready.</p>
                <ol class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($this->getSetupChecklist() as $step)
                        <li class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <a class="font-medium text-indigo-600 hover:underline" href="{{ $step['url'] }}">{{ $step['complete'] ? '✓' : '○' }} {{ $step['label'] }} →</a>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $step['complete'] ? 'Demo step available. ' : '' }}{{ $step['detail'] }}</p>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Start with a text lesson and one assignment. File resources are available when your school’s upload checks are ready.</p>
            </section>
        @endif
        @php($tasks = $this->getTaskSummary())
        @if ($tasks['pending'] > 0)
            <section class="rounded-xl border border-indigo-200 bg-white p-5 dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Responses awaiting feedback release: {{ $tasks['pending'] }}</h2>
                <div class="mt-3 grid gap-3">
                    @foreach ($tasks['responses'] as $response)
                        <a class="text-indigo-600 hover:underline" href="{{ route('filament.school.pages.school-learning', ['tenant' => $school->slug, 'view' => 'review', 'course' => $response->assignment->school_course_id, 'assignment' => $response->school_learning_assignment_id, 'response' => $response->id]) }}">{{ $response->learnerProfile->first_name }} {{ $response->learnerProfile->last_name }} · {{ $response->assignment->title }} →</a>
                    @endforeach
                </div>
            </section>
        @endif
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($this->getAvailableWorkflows() as $workflow)
                <a class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-gray-900 dark:hover:border-indigo-500" href="{{ $workflow['url'] }}">
                    <h3 class="font-semibold text-gray-950 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">{{ $workflow['label'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $workflow['description'] }}</p>
                    <span class="mt-4 inline-flex text-sm font-medium text-indigo-600 dark:text-indigo-400">Open →</span>
                </a>
            @endforeach
        </section>
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">School type</p>
                <p class="mt-2 text-lg font-semibold capitalize text-gray-950 dark:text-white">{{ $school->school_type }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Membership</p>
                <p class="mt-2 text-lg font-semibold capitalize text-gray-950 dark:text-white">{{ $membership->status }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Your scoped roles</p>
                <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $membership->roles->pluck('role')->map(fn ($role): string => str($role->value)->replace('_', ' ')->title()->toString())->join(', ') }}</p>
            </article>
        </section>
    </div>
</x-filament-panels::page>
