<?php

namespace Database\Factories;

use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeChargeBatch>
 */
class FeeChargeBatchFactory extends Factory
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
            'fee_schedule_id' => FeeSchedule::factory(),
            'created_by_user_id' => User::factory(),
            'batch_key' => fake()->unique()->bothify('batch-####'),
            'status' => 'draft',
            'eligible_count' => 0,
            'total_minor' => 0,
            'posted_at' => null,
        ];
    }
}
