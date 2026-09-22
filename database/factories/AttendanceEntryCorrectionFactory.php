<?php

namespace Database\Factories;

use App\Models\AttendanceEntry;
use App\Models\AttendanceEntryCorrection;
use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceEntryCorrection>
 */
class AttendanceEntryCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_entry_id' => AttendanceEntry::factory(),
            'attendance_session_id' => AttendanceSession::factory(),
            'from_status' => 'unmarked',
            'to_status' => 'present',
            'reason' => fake()->sentence(),
            'corrected_by_user_id' => User::factory(),
            'session_version' => 1,
            'corrected_at' => now(),
        ];
    }
}
