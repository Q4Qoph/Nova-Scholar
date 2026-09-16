<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">AI tutor</h2></x-slot>
    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        @foreach($chat->messages as $message)
            <div class="rounded-xl p-4 {{ $message->role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-900' }}">
                @if($message->role === 'assistant')
                    <div class="max-w-none">{!! app(\App\Support\MarkdownRenderer::class)->render($message->content) !!}</div>
                @else
                    <p class="whitespace-pre-wrap">{{ $message->content }}</p>
                @endif
                @if($message->status === 'pending')<span class="text-sm">Thinking…</span>@elseif($message->status === 'failed')<span class="text-sm text-rose-200">Unable to answer. Please try again.</span>@endif
            </div>
        @endforeach
        <form method="POST" action="{{ route('chats.messages.store', $chat) }}" class="rounded-xl bg-white p-4">
            @csrf
            <textarea class="w-full rounded border-slate-300" name="content" required maxlength="4000"></textarea>
            <button class="mt-2 rounded bg-indigo-600 px-4 py-2 text-white">Ask tutor</button>
        </form>
    </div>
</x-app-layout>
