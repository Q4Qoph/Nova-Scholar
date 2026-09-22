<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['multiple_choice', 'short_answer', 'true_false']),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'question_count' => 5,
            'status' => 'complete',
            'request_key' => fake()->uuid(),
            'failure_code' => null,
        ];
    }
}
