<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-sm sm:p-8">
            <p class="text-sm font-medium text-amber-100">Platform workspace</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Keep school operations visible and controlled.</h2>
            <p class="mt-3 max-w-2xl text-amber-100">Provision school workspaces and review operational metadata without entering private school records.</p>
            <a class="mt-6 inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-amber-700 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-amber-600" href="{{ $this->getSchoolDirectoryUrl() }}">Open school directory</a>
        </section>

        <section class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">All schools</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->getSchoolsCount() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Active schools</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $this->getActiveSchoolsCount() }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Other statuses</p>
                <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->getSuspendedSchoolsCount() }}</p>
            </article>
        </section>
    </div>
</x-filament-panels::page>
