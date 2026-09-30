<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolCourse>
 */
class SchoolCourseFactory extends Factory
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
            'teaching_assignment_id' => TeachingAssignment::factory(),
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'status' => 'active',
        ];
    }
}
