<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'short_answer',
            'prompt' => fake()->sentence(),
            'options' => null,
            'answer' => fake()->sentence(),
            'explanation' => fake()->optional()->sentence(),
        ];
    }
}
