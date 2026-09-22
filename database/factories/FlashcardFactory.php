<?php

namespace Database\Factories;

use App\Models\Flashcard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flashcard>
 */
class FlashcardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'front' => fake()->sentence(),
            'back' => fake()->paragraph(),
        ];
    }
}
