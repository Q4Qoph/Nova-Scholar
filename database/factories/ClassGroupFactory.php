<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
class ClassGroupFactory extends Factory
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
            'name' => fake()->unique()->bothify('Class ##'),
            'grade_level' => fake()->randomElement(['Grade 1', 'Grade 2', 'Grade 5']),
            'stream' => fake()->randomElement(['A', 'B', null]),
            'status' => 'active',
        ];
    }
}
