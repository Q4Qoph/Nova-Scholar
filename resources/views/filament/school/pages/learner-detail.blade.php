<x-filament-panels::page>
    @php($school = $this->getSchool())
    @php($profile = $this->learner->learnerProfile)
    @php($guardians = $this->getGuardianLinks())
    @php($classMemberships = $this->getClassMemberships())

    <div class="grid gap-6">
        <section class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-1">
                <a class="text-sm font-medium text-primary-600 dark:text-primary-400" href="{{ $this->getRegistryUrl() }}">← Back to learner registry</a>
                <p class="pt-3 text-sm font-medium text-primary-600 dark:text-primary-400">{{ $school->name }}</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $profile->preferred_name ?: $profile->first_name }} {{ $profile->last_name }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Admission {{ $this->learner->admission_number }}</p>
                @if ($this->canViewFeeStatement())
                    <a class="inline-flex pt-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400" href="{{ $this->getFeeStatementUrl() }}">View fee statement</a>
                @endif
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                <p class="mt-2 font-semibold capitalize text-gray-950 dark:text-white">{{ $this->learner->status }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Enrolled on</p>
                <p class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $this->learner->enrolled_at?->toFormattedDateString() ?? 'Not recorded' }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Date of birth</p>
                <p class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $profile->date_of_birth?->toFormattedDateString() ?? 'Not recorded' }}</p>
            </article>
        </section>

        @if ($this->canManageAccess())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Managed learner access</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Learner sign-in</h3>
                @if ($this->learner->learnerProfile->user)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Login ID: <span class="font-semibold text-gray-950 dark:text-white">{{ $this->learner->learnerProfile->user->learner_login_id }}</span></p>
                @else
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Issue a one-time activation link for this learner.</p>
                    <button wire:click="issueManagedAccess" class="mt-4 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="button">Issue activation link</button>
                @endif
                @if ($activationUrl)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                        <p class="font-semibold">One-time activation link</p>
                        <a class="mt-1 block break-all underline" href="{{ $activationUrl }}">{{ $activationUrl }}</a>
                    </div>
                @endif
            </section>
        @endif

        @if ($this->canTransferLearner())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Lifecycle</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Transfer learner</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">You must be an administrator in both schools. Source history is retained.</p>
                <form class="mt-5 grid gap-4 md:grid-cols-3" wire:submit="transferLearner">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="transfer-destination-school">Destination school</label>
                        <select wire:model="transferDestinationSchoolId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="transfer-destination-school">
                            <option value="">Select school</option>
                            @foreach ($this->getTransferSchools() as $transferSchool)
                                <option value="{{ $transferSchool->id }}">{{ $transferSchool->name }}</option>
                            @endforeach
                        </select>
                        @error('transferDestinationSchoolId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="transfer-admission-number">New admission number</label>
                        <input wire:model="transferAdmissionNumber" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="transfer-admission-number" type="text">
                        @error('transferAdmissionNumber')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="transfer-date">Transfer date</label>
                        <input wire:model="transferDate" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="transfer-date" type="date">
                        @error('transferDate')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 md:col-span-3 md:justify-self-start" type="submit">Transfer learner</button>
                </form>
            </section>
        @endif

        @if ($this->canManageLifecycle())
            <section class="rounded-xl border border-danger-200 bg-danger-50 p-6 dark:border-danger-400/20 dark:bg-danger-400/10">
                <p class="text-sm font-medium text-danger-700 dark:text-danger-300">Access and enrolment control</p>
                <h3 class="mt-1 text-lg font-semibold text-danger-900 dark:text-danger-100">Deactivate learner</h3>
                <p class="mt-2 text-sm text-danger-800 dark:text-danger-200">This withdraws the enrolment, closes current class placement, and blocks managed learner access. History is retained.</p>
                <form class="mt-5 flex flex-wrap items-end gap-4" wire:submit="deactivateLearner">
                    <div>
                        <label class="block text-sm font-medium text-danger-800 dark:text-danger-200" for="deactivated-on">Deactivation date</label>
                        <input wire:model="deactivatedOn" class="mt-1 block rounded-lg border-danger-300 text-sm shadow-sm dark:border-danger-400/30 dark:bg-gray-950 dark:text-white" id="deactivated-on" type="date">
                        @error('deactivatedOn')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-danger-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-danger-500" type="submit">Deactivate learner</button>
                </form>
            </section>
        @endif

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Academic placement</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Class history</h3>
                <div class="mt-4 divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($classMemberships as $membership)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <p class="font-medium text-gray-950 dark:text-white">{{ $membership->classGroup->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $membership->classGroup->academicYear->name }} · {{ $membership->starts_on->toFormattedDateString() }} – {{ $membership->ends_on?->toFormattedDateString() ?? 'Current' }}</p>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-gray-500 dark:text-gray-400">No class placements have been recorded.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Verified relationships</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Guardians</h3>
                <div class="mt-4 divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($guardians as $link)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <p class="font-medium text-gray-950 dark:text-white">{{ $link->guardian->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link->guardian->email }} · {{ str($link->relationship)->title() }}</p>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-gray-500 dark:text-gray-400">No active guardian links.</p>
                    @endforelse
                </div>
            </article>
        </section>

        @if ($this->canManagePlacements())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Progression</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Promote to a new class</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">The current placement will end the day before the new placement begins.</p>
                <form class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end" wire:submit="promoteLearner">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="promotion-class-group">New class group</label>
                        <select wire:model="promotionClassGroupId" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="promotion-class-group">
                            <option value="">Select class</option>
                            @foreach ($this->getPromotionClassGroups() as $classGroup)
                                <option value="{{ $classGroup->id }}">{{ $classGroup->academicYear->name }} · {{ $classGroup->name }}</option>
                            @endforeach
                        </select>
                        @error('promotionClassGroupId')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="promotion-starts-on">Promotion date</label>
                        <input wire:model="promotionStartsOn" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="promotion-starts-on" type="date">
                        @error('promotionStartsOn')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Record promotion</button>
                </form>
            </section>
        @endif

        @if ($this->canManageGuardians())
            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Relationship verification</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Link a guardian</h3>
                    <form class="mt-5 space-y-4" wire:submit="linkGuardian">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="guardian-email">Verified user email</label>
                            <input wire:model="guardianEmail" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="guardian-email" type="email">
                            @error('guardianEmail')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="guardian-relationship">Relationship</label>
                            <input wire:model="guardianRelationship" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="guardian-relationship" type="text">
                            @error('guardianRelationship')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                        <button class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Verify relationship</button>
                    </form>
                </article>

                <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Active links</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Guardians</h3>
                    <div class="mt-4 divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($guardians as $link)
                            <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-gray-950 dark:text-white">{{ $link->guardian->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link->guardian->email }} · {{ str($link->relationship)->title() }}</p>
                                </div>
                                <button wire:click="revokeGuardian({{ $link->id }})" class="text-sm font-medium text-danger-600 hover:text-danger-500" type="button">Revoke</button>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-gray-500 dark:text-gray-400">No active guardian links.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif
    </div>
</x-filament-panels::page>
