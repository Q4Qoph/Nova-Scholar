<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Flashcards</h2></x-slot>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
        <form method="POST" action="{{ route('flashcards.store') }}" class="rounded-xl bg-white p-6 shadow-sm">@csrf<h3 class="font-semibold">Generate flashcards</h3><div class="mt-4 flex gap-3"><input class="flex-1 rounded border-slate-300" name="topic" placeholder="Topic" required maxlength="200"><input class="w-24 rounded border-slate-300" type="number" name="count" value="10" min="1" max="50" required><button class="rounded bg-indigo-600 px-4 py-2 text-white">Generate</button></div></form>
        <div class="space-y-3">@forelse($decks as $deck)<a class="block rounded-xl bg-white p-4 shadow-sm" href="{{ route('flashcards.show', $deck) }}"><span class="font-medium">{{ $deck->title }}</span><span class="ml-2 text-sm text-slate-500">{{ $deck->status }}</span></a>@empty<p class="text-slate-600">No flashcard decks yet.</p>@endforelse</div>
    </div>
</x-app-layout>
