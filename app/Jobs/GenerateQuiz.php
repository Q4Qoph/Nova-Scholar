<?php

namespace App\Jobs;

use App\Models\Quiz;
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

class GenerateQuiz implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quiz $quiz, public string $topic, public AiExecutionContext $context = AiExecutionContext::Personal) {}

    /**
     * Execute the job.
     */
    public function handle(GroqChatService $groq, UsageService $usage, AiAvailability $availability): void
    {
        $quiz = $this->quiz->fresh();
        if ($quiz === null || $quiz->status !== 'pending') {
            return;
        }
        if (! $availability->allowsProvider($this->context)) {
            $this->failBlockedQuiz($quiz, $usage);

            return;
        }
        try {
            $raw = $groq->complete([
                ['role' => 'system', 'content' => 'Return only valid JSON with a questions array. Each item must contain type, prompt, answer, explanation, and options only for multiple_choice.'],
                ['role' => 'user', 'content' => json_encode(['topic' => $this->topic, 'type' => $quiz->type, 'difficulty' => $quiz->difficulty, 'question_count' => $quiz->question_count], JSON_THROW_ON_ERROR)],
            ]);
            $payload = json_decode(trim(str_replace(['```json', '```'], '', $raw)), true, 512, JSON_THROW_ON_ERROR);
            $questions = $payload['questions'] ?? null;
            if (! is_array($questions) || count($questions) !== $quiz->question_count) {
                throw new RuntimeException('Quiz response question count is invalid.');
            }
            DB::transaction(function () use ($quiz, $questions): void {
                foreach ($questions as $question) {
                    if (! is_array($question) || ($question['type'] ?? '') !== $quiz->type || ! is_string($question['prompt'] ?? null) || ! is_string($question['answer'] ?? null)) {
                        throw new RuntimeException('Quiz response shape is invalid.');
                    }
                    $options = $question['options'] ?? null;
                    if ($quiz->type === 'multiple_choice' && (! is_array($options) || count($options) < 2 || count($options) !== count(array_unique($options)))) {
                        throw new RuntimeException('Quiz options are invalid.');
                    }
                    $quiz->questions()->create(['type' => $question['type'], 'prompt' => $question['prompt'], 'options' => $options, 'answer' => $question['answer'], 'explanation' => $question['explanation'] ?? null]);
                }
                $quiz->update(['status' => 'complete']);
            });
            $reservation = UsageReservation::query()->where('request_key', $quiz->request_key)->first();
            if ($reservation !== null) {
                $usage->settle($reservation, 1);
            }
        } catch (Throwable $exception) {
            $quiz->update(['status' => 'failed', 'failure_code' => 'invalid_provider_output']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $quiz = $this->quiz->fresh();
        if ($quiz?->status === 'pending') {
            $quiz->update(['status' => 'failed', 'failure_code' => 'generation_failed']);
        }
        $reservation = UsageReservation::query()->where('request_key', $this->quiz->request_key)->first();
        if ($reservation?->status === 'pending') {
            app(UsageService::class)->release($reservation);
        }
    }

    private function failBlockedQuiz(Quiz $quiz, UsageService $usage): void
    {
        $quiz->update(['status' => 'failed', 'failure_code' => 'ai_disabled']);
        $reservation = UsageReservation::query()->where('request_key', $quiz->request_key)->first();
        if ($reservation?->status === 'pending') {
            $usage->release($reservation);
        }
    }
}
