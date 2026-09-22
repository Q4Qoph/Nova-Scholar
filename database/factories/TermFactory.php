<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
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
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->randomElement(['Term 1', 'Term 2', 'Term 3']),
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->startOfYear()->addMonths(3)->subDay()->toDateString(),
            'status' => 'open',
        ];
    }
}
