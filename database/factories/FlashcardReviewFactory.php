<?php

namespace Database\Factories;

use App\Models\FlashcardReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashcardReview>
 */
class FlashcardReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outcome' => fake()->randomElement(['again', 'hard', 'good', 'easy']),
            'reviewed_at' => now(),
        ];
    }
}
