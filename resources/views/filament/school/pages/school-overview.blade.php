<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($membership = $this->getCurrentMembership())

    <div class="grid gap-6">
        <section class="rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
            <p class="text-sm font-medium text-indigo-100">{{ $school->name }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Keep school operations and learning connected.</h2>
            <p class="mt-3 max-w-2xl text-indigo-100">Your active membership and scoped roles apply to the workflows available in this school panel.</p>
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

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($this->getAvailableWorkflows() as $workflow)
                <a class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-gray-900 dark:hover:border-indigo-500" href="{{ $workflow['url'] }}">
                    <h3 class="font-semibold text-gray-950 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">{{ $workflow['label'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $workflow['description'] }}</p>
                    <span class="mt-4 inline-flex text-sm font-medium text-indigo-600 dark:text-indigo-400">Open →</span>
                </a>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
