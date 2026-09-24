<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <x-slot name="heading">Draft a guardian notice</x-slot>
            <x-slot name="description">Review the audience before sending. Delivery is in app only in this release.</x-slot>

            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        Audience
                        <select wire:model.live="audienceType" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                            <option value="all_guardians">All active guardians</option>
                            <option value="class_guardians">Guardians of one class</option>
                        </select>
                        @error('audienceType')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                    </label>

                    @if ($audienceType === 'class_guardians')
                        <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                            Active class
                            <select wire:model="classGroupId" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                                <option value="">Select a class</option>
                                @foreach ($this->getActiveClassGroups() as $classGroup)
                                    <option value="{{ $classGroup->id }}">{{ $classGroup->name }}</option>
                                @endforeach
                            </select>
                            @error('classGroupId')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                        </label>
                    @endif
                </div>

                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Title
                    <input wire:model="draftTitle" type="text" maxlength="255" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white">
                    @error('draftTitle')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Message
                    <textarea wire:model="messageBody" rows="5" maxlength="10000" class="fi-input block w-full rounded-lg border-gray-300 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-950 dark:text-white"></textarea>
                    @error('messageBody')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>

                <div>
                    <x-filament::button wire:click="createDraft" wire:loading.attr="disabled" icon="heroicon-m-document-plus">
                        Save draft
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Notice history</x-slot>
            <x-slot name="description">Drafts and sent notices for this school.</x-slot>

            <div class="grid gap-4">
                @forelse ($this->getAnnouncements() as $announcement)
                    <article class="grid gap-3 rounded-xl border border-gray-200 p-4 dark:border-white/10" wire:key="announcement-{{ $announcement->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="grid gap-1">
                                <h3 class="font-semibold text-gray-950 dark:text-white">{{ $announcement->title }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $announcement->audience_type === 'class_guardians' ? $announcement->classGroup?->name : 'All active guardians' }}
                                    · {{ ucfirst($announcement->status) }}
                                    @if ($announcement->status === 'sent')
                                        · {{ $announcement->deliveries_count }} in-app deliveries
                                    @endif
                                </p>
                            </div>

                            @if ($announcement->status === 'draft')
                                <x-filament::button
                                    wire:click="sendAnnouncement({{ $announcement->id }})"
                                    wire:confirm="Send this notice to the current matching guardians?"
                                    wire:loading.attr="disabled"
                                    color="primary"
                                    icon="heroicon-m-paper-airplane"
                                >
                                    Send in app
                                </x-filament::button>
                            @else
                                <x-filament::badge color="success">Sent</x-filament::badge>
                            @endif
                        </div>

                        <p class="whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $announcement->body }}</p>
                    </article>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">No notices have been drafted yet.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
