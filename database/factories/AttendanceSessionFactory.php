<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
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
            'teaching_assignment_id' => TeachingAssignment::factory(),
            'session_date' => today(),
            'status' => 'open',
            'version' => 0,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => User::factory(),
        ];
    }
}
