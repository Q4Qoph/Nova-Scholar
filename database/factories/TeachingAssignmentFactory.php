<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeachingAssignment>
 */
class TeachingAssignmentFactory extends Factory
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
            'class_group_id' => ClassGroup::factory(),
            'subject_id' => Subject::factory(),
            'teacher_user_id' => User::factory(),
            'status' => 'active',
        ];
    }
}
