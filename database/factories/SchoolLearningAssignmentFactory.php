<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLessonVersion;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SchoolLearningAssignment>
 */
class SchoolLearningAssignmentFactory extends Factory
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
            'school_course_id' => SchoolCourse::factory(),
            'source_lesson_version_id' => SchoolLessonVersion::factory(),
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'instructions' => fake()->paragraph(),
            'submission_type' => 'text',
            'due_at' => Carbon::now()->addDays(7),
            'cutoff_at' => null,
            'status' => SchoolLearningAssignmentStatus::Draft->value,
            'published_at' => null,
        ];
    }

    public function forPublishedLesson(School $school, TeachingAssignment $teachingAssignment, SchoolCourse $course, SchoolLessonVersion $lessonVersion): static
    {
        return $this->state(fn (array $attributes): array => [
            'school_id' => $school->id,
            'teaching_assignment_id' => $teachingAssignment->id,
            'school_course_id' => $course->id,
            'source_lesson_version_id' => $lessonVersion->id,
        ]);
    }
}
