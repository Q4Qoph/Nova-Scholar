<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveSchoolLearningAssignmentDraft
{
    public function handle(
        User $actor,
        School $school,
        TeachingAssignment $teachingAssignment,
        SchoolCourse $course,
        int $lessonVersionId,
        string $title,
        string $instructions,
        string $dueAt,
        ?string $cutoffAt = null,
        ?int $draftId = null,
    ): SchoolLearningAssignment {
        return DB::transaction(function () use (
            $actor,
            $school,
            $teachingAssignment,
            $course,
            $lessonVersionId,
            $title,
            $instructions,
            $dueAt,
            $cutoffAt,
            $draftId,
        ): SchoolLearningAssignment {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedAssignment = $lockedSchool->teachingAssignments()
                ->whereKey($teachingAssignment->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('createForAssignment', [SchoolLearningAssignment::class, $lockedAssignment]);

            $lockedCourse = $lockedSchool->lessonCourses()
                ->whereKey($course->id)
                ->where('teaching_assignment_id', $lockedAssignment->id)
                ->where('status', 'active')
                ->firstOrFail();
            $lessonVersion = SchoolLessonVersion::query()
                ->whereKey($lessonVersionId)
                ->where('status', SchoolLessonVersionStatus::Published->value)
                ->whereHas('lesson', fn ($query) => $query->whereHas('course', fn ($courseQuery) => $courseQuery->whereKey($lockedCourse->id)))
                ->firstOrFail();

            $due = Carbon::parse($dueAt, $lockedSchool->timezone)->utc();
            $cutoff = $cutoffAt === null || trim($cutoffAt) === ''
                ? null
                : Carbon::parse($cutoffAt, $lockedSchool->timezone)->utc();

            if ($due->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['assignmentDueAt' => 'The due date must be in the future.']);
            }

            if ($cutoff !== null && $cutoff->lessThan($due)) {
                throw ValidationException::withMessages(['assignmentCutoffAt' => 'The submission cutoff cannot be before the due date.']);
            }

            if ($draftId === null) {
                $draft = $lockedSchool->learningAssignments()->create([
                    'teaching_assignment_id' => $lockedAssignment->id,
                    'school_course_id' => $lockedCourse->id,
                    'source_lesson_version_id' => $lessonVersion->id,
                    'created_by_user_id' => $actor->id,
                    'title' => trim($title),
                    'instructions' => trim($instructions),
                    'submission_type' => 'text',
                    'due_at' => $due,
                    'cutoff_at' => $cutoff,
                    'status' => SchoolLearningAssignmentStatus::Draft,
                ]);
            } else {
                $draft = $lockedSchool->learningAssignments()
                    ->whereKey($draftId)
                    ->where('status', SchoolLearningAssignmentStatus::Draft->value)
                    ->lockForUpdate()
                    ->firstOrFail();
                Gate::authorize('update', $draft);
                $draft->forceFill([
                    'teaching_assignment_id' => $lockedAssignment->id,
                    'school_course_id' => $lockedCourse->id,
                    'source_lesson_version_id' => $lessonVersion->id,
                    'title' => trim($title),
                    'instructions' => trim($instructions),
                    'due_at' => $due,
                    'cutoff_at' => $cutoff,
                ])->save();
            }

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_learning_assignment.draft_saved',
                'auditable_type' => SchoolLearningAssignment::class,
                'auditable_id' => $draft->id,
                'metadata' => ['course_id' => $lockedCourse->id, 'lesson_version_id' => $lessonVersion->id],
                'occurred_at' => now(),
            ]);

            return $draft->refresh();
        });
    }
}
