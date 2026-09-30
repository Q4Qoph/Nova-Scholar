<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-gray-900">{{ __('Choose your workspace') }}</h1></x-slot>
    <main class="mx-auto grid max-w-5xl gap-4 p-6 sm:grid-cols-2">
        @foreach ($destinations as $destination)
            <a class="rounded-xl border border-gray-200 bg-white p-6 text-indigo-700 shadow-sm focus-visible:outline-2 focus-visible:outline-indigo-600" href="{{ $destination['url'] }}">{{ $destination['label'] }} →</a>
        @endforeach
        <a class="rounded-xl border border-gray-200 bg-white p-6 text-indigo-700 shadow-sm" href="{{ route('study') }}">{{ __('Personal study') }} →</a>
    </main>
</x-app-layout>
