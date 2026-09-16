<?php

namespace Tests\Feature;

use App\Jobs\GenerateChatResponse;
use App\Models\Chat;
use App\Models\User;
use App\Services\AI\GroqChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class GenerateChatResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_pending_prompt_and_stores_the_assistant_response(): void
    {
        $chat = Chat::factory()->for(User::factory())->create();
        $message = $chat->messages()->create([
            'role' => 'user',
            'content' => 'Explain photosynthesis.',
            'status' => 'pending',
            'request_key' => fake()->uuid(),
        ]);

        $this->mock(GroqChatService::class, function ($mock): void {
            $mock->shouldReceive('complete')
                ->once()
                ->with([['role' => 'user', 'content' => 'Explain photosynthesis.']])
                ->andReturn('Plants convert light into energy.');
        });

        (new GenerateChatResponse($message))->handle(app(GroqChatService::class));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'complete']);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Plants convert light into energy.',
            'status' => 'complete',
        ]);
    }

    public function test_failed_jobs_mark_pending_messages_as_failed(): void
    {
        $message = Chat::factory()->for(User::factory())->create()->messages()->create([
            'role' => 'user',
            'content' => 'What is a cell?',
            'status' => 'pending',
            'request_key' => fake()->uuid(),
        ]);

        (new GenerateChatResponse($message))->failed(new LogicException('provider unavailable'));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'failed']);
    }
}
