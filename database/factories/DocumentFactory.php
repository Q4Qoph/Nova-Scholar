<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'disk' => 'local',
            'path' => 'documents/'.fake()->uuid().'.txt',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 10000),
            'status' => 'ready',
            'extracted_text' => fake()->paragraph(),
            'failure_code' => null,
        ];
    }
}
