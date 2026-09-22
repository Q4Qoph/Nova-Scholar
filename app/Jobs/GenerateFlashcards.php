<?php

namespace App\Jobs;

use App\Models\FlashcardDeck;
use App\Models\UsageReservation;
use App\Services\AI\AiAvailability;
use App\Services\AI\AiExecutionContext;
use App\Services\AI\GroqChatService;
use App\Services\Usage\UsageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class GenerateFlashcards implements ShouldQueue
{
    use Queueable;

    public function __construct(public FlashcardDeck $deck, public string $topic, public int $count, public AiExecutionContext $context = AiExecutionContext::Personal) {}

    /**
     * Execute the job.
     */
    public function handle(GroqChatService $groq, UsageService $usage, AiAvailability $availability): void
    {
        $deck = $this->deck->fresh();
        if ($deck === null || $deck->status !== 'pending') {
            return;
        }
        if (! $availability->allowsProvider($this->context)) {
            $this->failBlockedDeck($deck, $usage);

            return;
        }
        try {
            $raw = $groq->complete([
                ['role' => 'system', 'content' => 'Return only valid JSON with a flashcards array. Each item must contain front and back strings.'],
                ['role' => 'user', 'content' => json_encode(['topic' => $this->topic, 'count' => $this->count], JSON_THROW_ON_ERROR)],
            ]);
            $payload = json_decode(trim(str_replace(['```json', '```'], '', $raw)), true, 512, JSON_THROW_ON_ERROR);
            $cards = $payload['flashcards'] ?? null;
            if (! is_array($cards) || count($cards) !== $this->count) {
                throw new RuntimeException('Flashcard response count is invalid.');
            }
            DB::transaction(function () use ($deck, $cards): void {
                foreach ($cards as $card) {
                    if (! is_array($card) || ! is_string($card['front'] ?? null) || ! is_string($card['back'] ?? null)) {
                        throw new RuntimeException('Flashcard response shape is invalid.');
                    }
                    $deck->flashcards()->create(['front' => $card['front'], 'back' => $card['back']]);
                }
                $deck->update(['status' => 'complete']);
            });
            $reservation = UsageReservation::query()->where('request_key', $deck->request_key)->first();
            if ($reservation !== null) {
                $usage->settle($reservation, 1);
            }
        } catch (Throwable $exception) {
            $deck->update(['status' => 'failed', 'failure_code' => 'invalid_provider_output']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $deck = $this->deck->fresh();
        if ($deck?->status === 'pending') {
            $deck->update(['status' => 'failed', 'failure_code' => 'generation_failed']);
        }
        $reservation = UsageReservation::query()->where('request_key', $this->deck->request_key)->first();
        if ($reservation?->status === 'pending') {
            app(UsageService::class)->release($reservation);
        }
    }

    private function failBlockedDeck(FlashcardDeck $deck, UsageService $usage): void
    {
        $deck->update(['status' => 'failed', 'failure_code' => 'ai_disabled']);
        $reservation = UsageReservation::query()->where('request_key', $deck->request_key)->first();
        if ($reservation?->status === 'pending') {
            $usage->release($reservation);
        }
    }
}
