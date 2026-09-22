<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'uploaded_by_user_id' => User::factory(),
            'source_filename' => 'learners.csv',
            'source_checksum' => fake()->sha256(),
            'status' => 'staged',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'committed_at' => null,
        ];
    }
}
