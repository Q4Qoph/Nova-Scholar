<?php

namespace Database\Factories;

use App\Models\SubscriptionPeriod;
use App\Models\UsageRecord;
use App\Models\UsageReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usage_reservation_id' => UsageReservation::factory(),
            'user_id' => User::factory(),
            'subscription_period_id' => SubscriptionPeriod::factory(),
            'feature_code' => 'ai_chat',
            'quantity' => 1,
            'estimated_cost_minor' => null,
            'actual_cost_minor' => null,
            'currency' => null,
            'occurred_at' => now(),
        ];
    }
}
