<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SchoolCourse;
use App\Models\SchoolLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLesson>
 */
class SchoolLessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_course_id' => SchoolCourse::factory(),
            'position' => fake()->unique()->numberBetween(1, 100),
        ];
    }
}
