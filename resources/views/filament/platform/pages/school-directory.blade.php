<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Platform operations</p>
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">School directory</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Review active and suspended school workspaces without entering their private records.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $this->schools->count() }} schools</span>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                <div class="hidden grid-cols-[minmax(0,1fr)_7rem_8rem_7rem] gap-4 bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 sm:grid dark:bg-white/5 dark:text-gray-400">
                    <span>School</span>
                    <span>Type</span>
                    <span>Status</span>
                    <span>Members</span>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($this->schools as $school)
                        <div class="grid gap-2 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_7rem_8rem_7rem] sm:items-center sm:gap-4">
                            <div>
                                <p class="font-medium text-gray-950 dark:text-white">{{ $school->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $school->slug }} · {{ $school->timezone }}</p>
                            </div>
                            <span class="text-sm capitalize text-gray-600 dark:text-gray-400">{{ $school->school_type }}</span>
                            <span class="w-fit rounded-full px-2.5 py-1 text-xs font-medium {{ $school->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">{{ str($school->status)->title() }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $school->memberships_count }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-sm text-gray-500 dark:text-gray-400">No school workspaces have been provisioned yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-1">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Controlled provisioning</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Provision a school</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Creates the school, first administrator membership, scoped role, and audit event together.</p>
            </div>

            <form class="mt-6 grid gap-4" wire:submit="provisionSchool">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="school-name">School name</label>
                    <input id="school-name" wire:model="schoolName" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white" required>
                    @error('schoolName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="school-slug">Slug</label>
                    <input id="school-slug" wire:model="schoolSlug" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white" placeholder="mwangaza-school" required>
                    @error('schoolSlug')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="school-type">School type</label>
                    <select id="school-type" wire:model="schoolType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="day">Day</option>
                        <option value="boarding">Boarding</option>
                        <option value="mixed">Mixed</option>
                    </select>
                    @error('schoolType')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="school-timezone">Timezone</label>
                    <input id="school-timezone" wire:model="timezone" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white" required>
                    @error('timezone')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="school-administrator">First school administrator</label>
                    <select id="school-administrator" wire:model="administratorId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white" required>
                        <option value="">Select a verified user</option>
                        @foreach ($this->administrators as $administrator)
                            <option value="{{ $administrator->id }}">{{ $administrator->name }} · {{ $administrator->email }}</option>
                        @endforeach
                    </select>
                    @error('administratorId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>

                <button class="mt-2 inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Provision school</span>
                    <span wire:loading>Provisioning…</span>
                </button>
            </form>
        </section>
    </div>
</x-filament-panels::page>
