<x-filament-panels::page>
    @php($school = $this->getSchool())

    <div class="grid gap-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="space-y-1">
                <p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ $school->name }}</p>
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Staff directory</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Review active staff and pending invitations in this school only.</p>
            </div>
        </section>

        @if ($this->canManageInvitations())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Staff setup</p>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Invite staff member</h2>
                </div>
                <form class="mt-5 grid gap-4 sm:grid-cols-3 sm:items-end" wire:submit="createInvitation">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="invitee-email">Verified account</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="invitee-email" wire:model="inviteeEmail" required>
                            <option value="">Select account</option>
                            @foreach ($this->getInviteeOptions() as $invitee)
                                <option value="{{ $invitee->email }}">{{ $invitee->name }} ({{ $invitee->email }})</option>
                            @endforeach
                        </select>
                        @error('inviteeEmail')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="invitee-role">Role</label>
                        <select class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" id="invitee-role" wire:model="inviteeRole" required>
                            <option value="school_admin">School admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="bursar">Bursar</option>
                        </select>
                        @error('inviteeRole')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500" type="submit">Create invitation</button>
                </form>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10"><h3 class="font-semibold text-gray-950 dark:text-white">Active members</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($this->getMemberships() as $membership)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div><p class="font-medium text-gray-950 dark:text-white">{{ $membership->user->name }}</p><p class="text-sm text-gray-500 dark:text-gray-400">{{ $membership->user->email }}</p></div>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @foreach ($membership->roles as $role)
                                <span class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium capitalize text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                                    {{ str($role->role->value)->replace('_', ' ')->toString() }}
                                    @if ($this->canManageInvitations())
                                        <button class="text-primary-700 hover:text-danger-600 dark:text-primary-400 dark:hover:text-danger-400" type="button" wire:click="removeRole({{ $membership->id }}, '{{ $role->role->value }}')" wire:confirm="Remove this role from the member?" aria-label="Remove {{ $role->role->value }} role">×</button>
                                    @endif
                                </span>
                            @endforeach
                            @if ($this->canManageInvitations())
                                <form class="flex flex-wrap items-center gap-2" wire:submit="assignRole({{ $membership->id }})">
                                    <select class="rounded-lg border-gray-300 py-1.5 text-xs shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white" wire:model="roleToAssign.{{ $membership->id }}" aria-label="Role to assign">
                                        <option value="">Add role</option>
                                        <option value="school_admin">School admin</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="bursar">Bursar</option>
                                    </select>
                                    <button class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5" type="submit">Assign</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No active members found.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10"><h3 class="font-semibold text-gray-950 dark:text-white">Pending invitations</h3></div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($this->getInvitations() as $invitation)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div><p class="font-medium text-gray-950 dark:text-white">{{ $invitation->invitee->email }}</p><p class="text-sm capitalize text-gray-500 dark:text-gray-400">{{ str($invitation->role->value)->replace('_', ' ')->toString() }} · expires {{ $invitation->expires_at->toFormattedDateString() }}</p></div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">Pending</span>
                            @if ($this->canManageInvitations())
                                <button class="text-sm font-medium text-danger-600 hover:text-danger-500 dark:text-danger-400" type="button" wire:click="revokeInvitation({{ $invitation->id }})" wire:confirm="Revoke this invitation?">Revoke</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">No pending invitations.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
