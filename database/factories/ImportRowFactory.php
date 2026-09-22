<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRow>
 */
class ImportRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_batch_id' => ImportBatch::factory(),
            'row_number' => fake()->unique()->numberBetween(2, 1000),
            'payload' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'preferred_name' => null,
                'date_of_birth' => '2012-01-01',
                'admission_number' => fake()->unique()->bothify('ADM-###'),
            ],
            'validation_errors' => null,
            'status' => 'valid',
            'committed_enrolment_id' => null,
        ];
    }
}
