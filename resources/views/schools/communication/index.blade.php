<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-indigo-600">{{ __('Communications') }}</p>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $school->name }} {{ __('notices') }}</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
            @endif

            <a class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700" href="{{ route('schools.overview', $school) }}">← {{ __('Back to school overview') }}</a>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-indigo-600">{{ __('New notice') }}</p>
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('Draft a guardian notice') }}</h2>
                </div>
                <form class="mt-6 grid gap-4" method="POST" action="{{ route('schools.announcements.store', $school) }}">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="notice-audience">{{ __('Audience') }}</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="notice-audience" name="audience_type" required>
                                <option value="all_guardians">{{ __('All active guardians') }}</option>
                                <option value="class_guardians">{{ __('Guardians of a class') }}</option>
                            </select>
                            @error('audience_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="notice-class">{{ __('Class, if targeted') }}</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="notice-class" name="class_group_id">
                                <option value="">{{ __('School-wide notice') }}</option>
                                @foreach ($classGroups as $classGroup)
                                    <option value="{{ $classGroup->id }}">{{ $classGroup->name }}</option>
                                @endforeach
                            </select>
                            @error('class_group_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="notice-title">{{ __('Title') }}</label>
                        <input class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="notice-title" name="title" type="text" value="{{ old('title') }}" required>
                        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="notice-body">{{ __('Message') }}</label>
                        <textarea class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" id="notice-body" name="body" rows="5" required>{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-sm text-gray-500">{{ __('Sending creates an in-app delivery record for each current matching guardian. Email and SMS are not configured in this slice.') }}</p>
                    <button class="w-fit rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Save draft') }}</button>
                </form>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-indigo-600">{{ __('Notice history') }}</p>
                    <h2 class="text-xl font-semibold text-gray-900">{{ __('Drafts and sent notices') }}</h2>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($announcements as $announcement)
                        <article class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $announcement->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ $announcement->audience_type === 'class_guardians' ? $announcement->classGroup?->name : __('All active guardians') }}</p>
                                </div>
                                @if ($announcement->status === 'draft')
                                    <form method="POST" action="{{ route('schools.announcements.send', [$school, $announcement]) }}">
                                        @csrf
                                        <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700" type="submit">{{ __('Send in app') }}</button>
                                    </form>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ __('Sent') }}</span>
                                @endif
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $announcement->body }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No notices have been drafted yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
