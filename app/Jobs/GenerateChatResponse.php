<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AI\GroqChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateChatResponse implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Message $message) {}

    /**
     * Execute the job.
     */
    public function handle(GroqChatService $groq): void
    {
        $message = $this->message->fresh();
        if ($message === null || $message->status !== 'pending') {
            return;
        }
        $history = $message->chat->messages()
            ->where('status', 'complete')
            ->oldest()
            ->get(['role', 'content'])
            ->map(fn (Message $item): array => ['role' => $item->role, 'content' => $item->content])
            ->all();
        $history[] = ['role' => $message->role, 'content' => $message->content];
        $message->chat->messages()->create(['role' => 'assistant', 'content' => $groq->complete($history), 'status' => 'complete']);
        $message->update(['status' => 'complete']);
    }

    /**
     * Mark a provider failure without exposing provider details to the student.
     */
    public function failed(?Throwable $exception): void
    {
        $message = $this->message->fresh();

        if ($message?->status === 'pending') {
            $message->update(['status' => 'failed']);
        }
    }
}
