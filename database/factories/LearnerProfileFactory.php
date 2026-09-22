<?php

namespace Database\Factories;

use App\Models\LearnerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerProfile>
 */
class LearnerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'preferred_name' => null,
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
