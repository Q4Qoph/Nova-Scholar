<?php

namespace Database\Factories;

use App\Models\SubscriptionPeriod;
use App\Models\UsageReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageReservation>
 */
class UsageReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_period_id' => SubscriptionPeriod::factory(),
            'feature_code' => 'ai_chat',
            'request_key' => fake()->uuid(),
            'quantity' => 1,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
            'settled_at' => null,
            'released_at' => null,
        ];
    }
}
