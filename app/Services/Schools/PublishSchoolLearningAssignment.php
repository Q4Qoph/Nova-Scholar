<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PublishSchoolLearningAssignment
{
    public function handle(User $actor, School $school, SchoolLearningAssignment $assignment): SchoolLearningAssignment
    {
        return DB::transaction(function () use ($actor, $school, $assignment): SchoolLearningAssignment {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedAssignment = $lockedSchool->learningAssignments()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('view', $lockedAssignment);

            if ($lockedAssignment->status === SchoolLearningAssignmentStatus::Published->value) {
                return $lockedAssignment->loadCount('recipients');
            }

            Gate::authorize('update', $lockedAssignment);

            if ($lockedAssignment->status !== SchoolLearningAssignmentStatus::Draft->value) {
                throw ValidationException::withMessages(['assignment' => 'Only a draft assignment can be published.']);
            }

            if ($lockedAssignment->due_at->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['assignmentDueAt' => 'The due date must still be in the future when you publish.']);
            }

            if ($lockedAssignment->cutoff_at !== null && $lockedAssignment->cutoff_at->lessThan($lockedAssignment->due_at)) {
                throw ValidationException::withMessages(['assignmentCutoffAt' => 'The submission cutoff cannot be before the due date.']);
            }

            $teachingAssignment = $lockedAssignment->teachingAssignment()
                ->where('status', 'active')
                ->firstOrFail();
            $course = $lockedAssignment->course()
                ->where('status', 'active')
                ->where('teaching_assignment_id', $teachingAssignment->id)
                ->firstOrFail();
            $lessonVersion = $lockedAssignment->sourceLessonVersion()
                ->where('status', SchoolLessonVersionStatus::Published->value)
                ->whereHas('lesson', fn (Builder $query) => $query->where('school_course_id', $course->id))
                ->firstOrFail();

            $today = now($lockedSchool->timezone)->toDateString();
            $placements = LearnerClassMembership::query()
                ->where('school_id', $lockedSchool->id)
                ->where('class_group_id', $teachingAssignment->class_group_id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $today)
                ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))
                ->whereHas('enrolment', fn (Builder $query) => $query
                    ->where('school_id', $lockedSchool->id)
                    ->where('status', 'active')
                    ->whereHas('learnerProfile', fn (Builder $profileQuery) => $profileQuery->where('status', 'active')))
                ->with('enrolment')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->unique('enrolment_id');

            if ($placements->isEmpty()) {
                throw ValidationException::withMessages(['assignment' => 'There are no active learners in this class to receive the assignment.']);
            }

            $assignedAt = now();
            foreach ($placements as $placement) {
                $enrolment = $placement->enrolment;

                $lockedAssignment->recipients()->create([
                    'school_id' => $lockedSchool->id,
                    'learner_profile_id' => $enrolment->learner_profile_id,
                    'enrolment_id' => $enrolment->id,
                    'learner_class_membership_id' => $placement->id,
                    'assigned_at' => $assignedAt,
                ]);
            }

            $lockedAssignment->forceFill([
                'status' => SchoolLearningAssignmentStatus::Published,
                'published_at' => $assignedAt,
            ])->save();

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_learning_assignment.published',
                'auditable_type' => SchoolLearningAssignment::class,
                'auditable_id' => $lockedAssignment->id,
                'metadata' => [
                    'course_id' => $course->id,
                    'lesson_version_id' => $lessonVersion->id,
                    'recipient_count' => $placements->count(),
                ],
                'occurred_at' => $assignedAt,
            ]);

            return $lockedAssignment->loadCount('recipients');
        });
    }
}
