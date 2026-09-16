<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_subscription_page(): void
    {
        $this->get(route('subscription.index'))->assertRedirect(route('login'));
    }

    public function test_verified_student_sees_public_plans_without_a_checkout_action(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create([
            'name' => 'Student plan',
            'is_public' => true,
        ]);
        PlanFeature::factory()->for($plan)->create([
            'feature_code' => 'ai_chat',
            'allowance' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertSee('No active subscription')
            ->assertSee('Student plan')
            ->assertSee('ai_chat: 10')
            ->assertDontSee('Checkout');
    }

    public function test_unverified_student_is_redirected_to_email_verification_before_subscription_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_seeded_catalogue_displays_the_approved_monthly_prices(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertSee('Student')
            ->assertSee('KES 299')
            ->assertSee('Pro')
            ->assertSee('KES 699');
    }
}
