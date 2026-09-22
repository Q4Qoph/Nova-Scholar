<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Quizzes</h2></x-slot>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
        <form method="POST" action="{{ route('quizzes.store') }}" class="rounded-xl bg-white p-6 shadow-sm">
            @csrf
            <h3 class="font-semibold text-slate-900">Generate a quiz</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <input class="rounded border-slate-300" name="topic" placeholder="Topic" required maxlength="200">
                <select class="rounded border-slate-300" name="type"><option value="multiple_choice">Multiple choice</option><option value="short_answer">Short answer</option><option value="true_false">True / False</option></select>
                <select class="rounded border-slate-300" name="difficulty"><option>easy</option><option selected>medium</option><option>hard</option></select>
                <input class="rounded border-slate-300" type="number" name="question_count" value="5" min="1" max="20" required>
            </div>
            <button class="mt-4 rounded bg-indigo-600 px-4 py-2 text-white">Generate quiz</button>
        </form>
        <div class="space-y-3">@forelse($quizzes as $quiz)<a class="block rounded-xl bg-white p-4 shadow-sm" href="{{ route('quizzes.show', $quiz) }}"><span class="font-medium">{{ $quiz->title }}</span><span class="ml-2 text-sm text-slate-500">{{ $quiz->status }}</span></a>@empty<p class="text-slate-600">No quizzes yet.</p>@endforelse</div>
    </div>
</x-app-layout>
