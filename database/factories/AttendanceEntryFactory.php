<?php

namespace Database\Factories;

use App\Models\AttendanceEntry;
use App\Models\AttendanceSession;
use App\Models\Enrolment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceEntry>
 */
class AttendanceEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'enrolment_id' => Enrolment::factory(),
            'status' => 'unmarked',
            'marked_at' => null,
            'marked_by_user_id' => null,
        ];
    }
}
