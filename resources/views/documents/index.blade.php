<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Document library</h2></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            <label class="block text-sm font-medium text-slate-700" for="document">Upload PDF, DOCX, or TXT (up to 100 MB)</label>
            <input class="mt-2 block w-full text-sm" id="document" name="document" type="file" accept=".pdf,.docx,.txt" required>
            @error('document')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white" type="submit">Upload document</button>
        </form>
        <div class="space-y-3">
            @forelse ($documents as $document)
                <article class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4"><div><p class="font-medium text-slate-900">{{ $document->title }}</p><p class="text-sm text-slate-600">{{ $document->status }} · {{ number_format($document->size / 1024) }} KB</p></div><form action="{{ route('documents.destroy', $document) }}" method="POST">@csrf @method('DELETE')<button class="text-sm font-medium text-red-600" type="submit">Delete</button></form></article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 p-6 text-sm text-slate-600">No documents uploaded yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
