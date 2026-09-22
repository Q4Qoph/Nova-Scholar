<?php

namespace Tests\Feature;

use App\Jobs\GenerateFlashcards;
use App\Jobs\GenerateQuiz;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\User;
use App\Services\AI\AiAvailability;
use App\Services\AI\AiExecutionContext;
use App\Services\AI\GroqChatService;
use App\Services\Usage\UsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class P5QuizFlashcardTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_request_a_quiz_and_flashcards(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createGenerationEntitlements($user);

        $this->actingAs($user)->post('/quizzes', ['topic' => 'Biology', 'type' => 'true_false', 'difficulty' => 'easy', 'question_count' => 3])->assertRedirect();
        $this->actingAs($user)->post('/flashcards', ['topic' => 'Biology', 'count' => 5])->assertRedirect();

        $this->assertDatabaseHas('quizzes', ['user_id' => $user->id, 'status' => 'pending', 'question_count' => 3]);
        $this->assertDatabaseHas('flashcard_decks', ['user_id' => $user->id, 'status' => 'pending']);
        Queue::assertPushed(GenerateQuiz::class);
        Queue::assertPushed(GenerateFlashcards::class);
    }

    public function test_users_cannot_view_other_users_learning_sets(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $quiz = Quiz::factory()->for($owner)->create();
        $deck = FlashcardDeck::factory()->for($owner)->create();

        $this->actingAs($other)->get('/quizzes/'.$quiz->id)->assertForbidden();
        $this->actingAs($other)->get('/flashcards/'.$deck->id)->assertForbidden();
    }

    public function test_malformed_quiz_output_fails_without_questions(): void
    {
        $quiz = Quiz::factory()->for(User::factory())->create(['status' => 'pending', 'question_count' => 2]);
        $this->mock(GroqChatService::class, fn ($mock) => $mock->shouldReceive('complete')->once()->andReturn('{"questions":[]}'));

        $this->expectExceptionMessage('Quiz response question count is invalid.');
        try {
            (new GenerateQuiz($quiz, 'Biology'))->handle(app(GroqChatService::class), app(UsageService::class), app(AiAvailability::class));
        } finally {
            $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'status' => 'failed']);
            $this->assertDatabaseCount('questions', 0);
        }
    }

    public function test_quiz_attempt_scores_objective_answers_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $quiz = Quiz::factory()->for($user)->create(['status' => 'complete', 'type' => 'true_false']);
        $first = Question::factory()->for($quiz)->create(['type' => 'true_false', 'answer' => 'true']);
        $second = Question::factory()->for($quiz)->create(['type' => 'true_false', 'answer' => 'false']);

        $start = $this->actingAs($user)->post('/quizzes/'.$quiz->id.'/attempts');
        $attempt = $quiz->attempts()->first();
        $start->assertRedirectToRoute('quizzes.attempts.show', [$quiz, $attempt]);
        $this->actingAs($user)->post('/quizzes/'.$quiz->id.'/attempts/'.$attempt->id, ['answers' => [$first->id => 'true', $second->id => 'true']])->assertRedirect();
        $this->actingAs($user)->post('/quizzes/'.$quiz->id.'/attempts/'.$attempt->id, ['answers' => [$first->id => 'false', $second->id => 'false']])->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt->id, 'status' => 'submitted', 'score' => 1, 'max_score' => 2]);
        $this->assertDatabaseCount('quiz_answers', 2);
    }

    public function test_flashcard_review_requires_owned_card_and_records_outcome(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $deck = FlashcardDeck::factory()->for($user)->create(['status' => 'complete']);
        $card = Flashcard::factory()->for($deck, 'deck')->create();

        $this->actingAs($user)->post('/flashcards/'.$deck->id.'/'.$card->id.'/review', ['outcome' => 'good'])->assertRedirectToRoute('flashcards.show', $deck);
        $this->assertDatabaseHas('flashcard_reviews', ['flashcard_id' => $card->id, 'user_id' => $user->id, 'outcome' => 'good']);
    }

    public function test_school_context_generation_is_blocked_for_quizzes_and_flashcards(): void
    {
        config(['ai.school_enabled' => false]);
        $user = User::factory()->create();
        $this->createGenerationEntitlements($user);
        $quiz = Quiz::factory()->for($user)->create(['status' => 'pending', 'request_key' => fake()->uuid()]);
        $deck = FlashcardDeck::factory()->for($user)->create(['status' => 'pending', 'request_key' => fake()->uuid()]);
        $quizReservation = app(UsageService::class)->reserve($user, 'quiz_generation', 1, $quiz->request_key);
        $deckReservation = app(UsageService::class)->reserve($user, 'flashcard_generation', 1, $deck->request_key);
        $this->mock(GroqChatService::class, fn ($mock) => $mock->shouldReceive('complete')->never());

        (new GenerateQuiz($quiz, 'Biology', AiExecutionContext::School))->handle(app(GroqChatService::class), app(UsageService::class), app(AiAvailability::class));
        (new GenerateFlashcards($deck, 'Biology', 3, AiExecutionContext::School))->handle(app(GroqChatService::class), app(UsageService::class), app(AiAvailability::class));

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'status' => 'failed', 'failure_code' => 'ai_disabled']);
        $this->assertDatabaseHas('flashcard_decks', ['id' => $deck->id, 'status' => 'failed', 'failure_code' => 'ai_disabled']);
        $this->assertDatabaseHas('usage_reservations', ['id' => $quizReservation->id, 'status' => 'released']);
        $this->assertDatabaseHas('usage_reservations', ['id' => $deckReservation->id, 'status' => 'released']);
    }

    private function createGenerationEntitlements(User $user): void
    {
        $plan = Plan::factory()->create();
        foreach (['quiz_generation', 'flashcard_generation'] as $featureCode) {
            PlanFeature::factory()->for($plan)->create(['feature_code' => $featureCode, 'allowance' => 10]);
        }
        $subscription = Subscription::factory()->for($plan)->for($user)->create();
        SubscriptionPeriod::factory()->for($subscription)->create();
    }
}
