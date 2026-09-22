<?php

namespace Tests\Feature;

use App\Jobs\GenerateChatResponse;
use App\Models\Chat;
use App\Models\Document;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_is_given_a_chat_and_can_view_it(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createChatEntitlement($user);

        $response = $this->actingAs($user)->get('/chat');

        $chat = $user->chats()->first();
        $response->assertRedirectToRoute('chats.show', $chat);
        $this->assertNotNull($chat);
    }

    public function test_guests_and_unverified_users_cannot_use_the_tutor(): void
    {
        $chat = Chat::factory()->for(User::factory())->create();

        $this->get('/chat')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/chat/'.$chat->id)->assertForbidden();
    }

    public function test_users_cannot_view_another_users_chat(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $chat = Chat::factory()->for($owner)->create();

        $this->actingAs($otherUser)->get('/chat/'.$chat->id)->assertForbidden();
    }

    public function test_valid_prompt_is_stored_and_queued(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $chat = Chat::factory()->for($user)->create();
        $this->createChatEntitlement($user);

        $response = $this->actingAs($user)->post('/chat/'.$chat->id.'/messages', [
            'content' => 'Explain Newton\'s first law.',
        ]);

        $message = $chat->messages()->first();
        $response->assertRedirectToRoute('chats.show', $chat);
        $this->assertNotNull($message);
        $this->assertSame('pending', $message->status);
        $this->assertSame('user', $message->role);
        Queue::assertPushed(GenerateChatResponse::class, function (GenerateChatResponse $job) use ($message): bool {
            return $job->message->is($message);
        });
    }

    public function test_only_owned_ready_documents_can_be_selected(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        $chat = Chat::factory()->for($user)->create();
        $readyDocument = Document::factory()->for($user)->create(['status' => 'ready']);
        $processingDocument = Document::factory()->for($user)->create(['status' => 'extracting']);
        $otherDocument = Document::factory()->for($otherUser)->create(['status' => 'ready']);

        $this->actingAs($user)->from('/chat/'.$chat->id)->post('/chat/'.$chat->id.'/messages', [
            'content' => 'Summarize my notes.',
            'documents' => [$readyDocument->id, $processingDocument->id, $otherDocument->id],
        ])->assertRedirect('/chat/'.$chat->id)->assertSessionHasErrors('documents');

        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('chat_document', 0);
    }

    public function test_blank_or_oversized_prompts_are_rejected(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $chat = Chat::factory()->for($user)->create();

        $this->actingAs($user)->from('/chat/'.$chat->id)->post('/chat/'.$chat->id.'/messages', ['content' => ''])
            ->assertRedirect('/chat/'.$chat->id)
            ->assertSessionHasErrors('content');
        $this->actingAs($user)->from('/chat/'.$chat->id)->post('/chat/'.$chat->id.'/messages', ['content' => str_repeat('a', 4001)])
            ->assertRedirect('/chat/'.$chat->id)
            ->assertSessionHasErrors('content');
        Queue::assertNothingPushed();

    }

    private function createChatEntitlement(User $user): void
    {
        $plan = Plan::factory()->create();
        PlanFeature::factory()->for($plan)->create(['feature_code' => 'ai_chat', 'allowance' => 10]);
        $subscription = Subscription::factory()->for($plan)->for($user)->create();
        SubscriptionPeriod::factory()->for($subscription)->create();
    }
}
