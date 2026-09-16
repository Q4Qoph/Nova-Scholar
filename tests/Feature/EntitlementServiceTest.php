<?php

namespace Tests\Feature;

use App\Exceptions\EntitlementDenied;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\SubscriptionPeriod;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use App\Services\Usage\UsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_period_exposes_only_its_configured_feature_allowance(): void
    {
        $user = User::factory()->create();
        $this->create_active_entitlement($user, 'ai_chat', 3);

        $entitlements = app(EntitlementService::class);

        $this->assertSame(3, $entitlements->allowanceFor($user, 'ai_chat'));
        $this->assertNull($entitlements->allowanceFor($user, 'document_upload'));
    }

    public function test_expired_or_missing_period_does_not_expose_an_allowance(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create();
        SubscriptionPeriod::factory()->for($subscription)->create([
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subSecond(),
        ]);

        $entitlements = app(EntitlementService::class);

        $this->assertNull($entitlements->allowanceFor($user, 'ai_chat'));
    }

    public function test_repeated_request_key_reuses_the_existing_usage_reservation(): void
    {
        $user = User::factory()->create();
        $this->create_active_entitlement($user, 'ai_chat', 3);
        $usage = app(UsageService::class);

        $firstReservation = $usage->reserve($user, 'ai_chat', 2, 'chat-request-1');
        $secondReservation = $usage->reserve($user, 'ai_chat', 2, 'chat-request-1');

        $this->assertSame($firstReservation->id, $secondReservation->id);
        $this->assertDatabaseCount('usage_reservations', 1);
    }

    public function test_reservation_cannot_exceed_the_remaining_feature_allowance(): void
    {
        $user = User::factory()->create();
        $this->create_active_entitlement($user, 'ai_chat', 3);
        $usage = app(UsageService::class);
        $usage->reserve($user, 'ai_chat', 2, 'chat-request-1');

        $this->expectException(EntitlementDenied::class);
        $usage->reserve($user, 'ai_chat', 2, 'chat-request-2');
    }

    public function test_settling_a_reservation_records_actual_usage_once(): void
    {
        $user = User::factory()->create();
        $this->create_active_entitlement($user, 'ai_chat', 3);
        $usage = app(UsageService::class);
        $reservation = $usage->reserve($user, 'ai_chat', 3, 'chat-request-1');

        $usage->settle($reservation, 2, 15, 'KES');
        $usage->settle($reservation, 2, 15, 'KES');

        $this->assertDatabaseHas('usage_reservations', ['id' => $reservation->id, 'status' => 'settled']);
        $this->assertDatabaseCount('usage_records', 1);
        $this->assertDatabaseHas('usage_records', ['usage_reservation_id' => $reservation->id, 'quantity' => 2, 'actual_cost_minor' => 15, 'currency' => 'KES']);
    }

    public function test_releasing_a_reservation_restores_capacity_without_creating_usage(): void
    {
        $user = User::factory()->create();
        $this->create_active_entitlement($user, 'ai_chat', 2);
        $usage = app(UsageService::class);
        $reservation = $usage->reserve($user, 'ai_chat', 2, 'chat-request-1');

        $usage->release($reservation);
        $nextReservation = $usage->reserve($user, 'ai_chat', 2, 'chat-request-2');

        $this->assertSame('released', $reservation->refresh()->status);
        $this->assertSame('pending', $nextReservation->status);
        $this->assertDatabaseCount('usage_records', 0);
    }

    private function create_active_entitlement(User $user, string $featureCode, ?int $allowance): SubscriptionPeriod
    {
        $plan = Plan::factory()->create();
        PlanFeature::factory()->for($plan)->create([
            'feature_code' => $featureCode,
            'allowance' => $allowance,
        ]);
        $subscription = Subscription::factory()->for($plan)->for($user)->create();

        return SubscriptionPeriod::factory()->for($subscription)->create();
    }
}
