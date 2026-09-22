<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
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
            'name' => (string) fake()->unique()->year(),
            'starts_on' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'ends_on' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'status' => 'open',
        ];
    }
}
