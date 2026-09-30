<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <x-slot name="heading">Open a class register</x-slot>
            <x-slot name="description">Choose one of your active class assignments and a date. Learners begin as unmarked.</x-slot>

            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        Class and subject
                        <select wire:model="teachingAssignmentId" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white">
                            <option value="">Select assignment</option>
                            @foreach ($this->getAssignments() as $assignment)
                                <option value="{{ $assignment->id }}">{{ $assignment->classGroup->name }} · {{ $assignment->subject->name }}</option>
                            @endforeach
                        </select>
                        @error('teachingAssignmentId')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        Session date
                        <input wire:model="sessionDate" type="date" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-gray-950 dark:text-white">
                        @error('sessionDate')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                    </label>
                </div>

                <x-filament::button wire:click="openRegister" wire:loading.attr="disabled" icon="heroicon-m-arrow-right">
                    Open register
                </x-filament::button>
            </div>
        </x-filament::section>

        @php($session = $this->getAttendanceSession())
        @if ($this->registerReady)
            <x-filament::section>
                <x-slot name="heading">{{ $session?->classGroup->name ?? $this->getAssignments()->firstWhere('id', $this->teachingAssignmentId)?->classGroup->name }} · {{ $session?->teachingAssignment->subject->name ?? $this->getAssignments()->firstWhere('id', $this->teachingAssignmentId)?->subject->name }}</x-slot>
                <x-slot name="description">{{ $session?->session_date->toFormattedDateString() ?? \Illuminate\Support\Carbon::parse($this->sessionDate)->toFormattedDateString() }}{{ $session ? ' · Version '.$session->version : '' }}. Changing an existing status requires a reason.</x-slot>

                <div class="grid gap-4">
                    @if ($this->getRegisterRoster() === [])
                        <p class="text-sm text-gray-600 dark:text-gray-400">No learner records are available for this register.</p>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                            <table class="w-full min-w-[38rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Learner</th>
                                        <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Attendance status</th>
                                        <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Correction reason</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                    @foreach ($this->getRegisterRoster() as $enrolmentId => $learnerName)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                                {{ $learnerName }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <select aria-label="Attendance status for {{ $learnerName }}" wire:model="entries.{{ $enrolmentId }}.status" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                                                    @foreach (['unmarked', 'present', 'absent', 'late', 'excused'] as $status)
                                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('entries.'.$enrolmentId.'.status')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <input wire:model="entries.{{ $enrolmentId }}.correction_reason" type="text" maxlength="2000" aria-label="Correction reason for {{ $learnerName }}" placeholder="Required when changing status" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                                                @error('entries.'.$enrolmentId.'.correction_reason')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @error('version')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    @error('entries')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror

                    <div>
                        <x-filament::button wire:click="saveRegister" wire:loading.attr="disabled" icon="heroicon-m-check">
                            Save register
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
