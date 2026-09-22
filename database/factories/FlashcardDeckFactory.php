<?php

namespace Database\Factories;

use App\Models\FlashcardDeck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashcardDeck>
 */
class FlashcardDeckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'status' => 'complete',
            'request_key' => fake()->uuid(),
            'failure_code' => null,
        ];
    }
}
