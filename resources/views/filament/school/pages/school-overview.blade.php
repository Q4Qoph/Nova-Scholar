<x-filament-panels::page>
    @php($school = $this->getSchool())

    <div class="grid gap-6">
        <section class="rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
            <p class="text-sm font-medium text-indigo-100">{{ $school->name }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Keep school operations and learning connected.</h2>
            <p class="mt-3 max-w-2xl text-indigo-100">Use the current school workflows through one clearly scoped workspace. Each link preserves the existing policy and service boundary.</p>
            <a class="mt-6 inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-700" href="{{ $this->getSchoolOverviewUrl() }}">Open current overview</a>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Learners', 'description' => 'Open the school registry.', 'url' => $this->getLearnersUrl()],
                ['label' => 'Academics', 'description' => 'Manage years, terms, classes and subjects.', 'url' => $this->getAcademicsUrl()],
                ['label' => 'Attendance', 'description' => 'Open the protected register.', 'url' => $this->getAttendanceUrl()],
                ['label' => 'Fees', 'description' => 'Review school fee schedules.', 'url' => $this->getFeesUrl()],
                ['label' => 'Communications', 'description' => 'Draft and send school notices.', 'url' => $this->getCommunicationsUrl()],
            ] as $workflow)
                <a class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-gray-900 dark:hover:border-indigo-500" href="{{ $workflow['url'] }}">
                    <h3 class="font-semibold text-gray-950 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">{{ $workflow['label'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $workflow['description'] }}</p>
                    <span class="mt-4 inline-flex text-sm font-medium text-indigo-600 dark:text-indigo-400">Open →</span>
                </a>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
