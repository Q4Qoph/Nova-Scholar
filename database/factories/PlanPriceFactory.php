<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'amount_minor' => fake()->numberBetween(100, 100000),
            'currency' => 'KES',
            'starts_at' => now(),
            'ends_at' => null,
        ];
    }
}
