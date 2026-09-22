<?php

namespace Database\Factories;

use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeSchedule>
 */
class FeeScheduleFactory extends Factory
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
            'term_id' => null,
            'class_group_id' => null,
            'name' => 'Tuition',
            'currency' => 'KES',
            'amount_minor' => 250000,
            'starts_on' => today(),
            'ends_on' => null,
            'status' => 'active',
        ];
    }
}
