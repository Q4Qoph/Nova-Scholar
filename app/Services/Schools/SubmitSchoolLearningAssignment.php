<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLearningSubmissionStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitSchoolLearningAssignment
{
    public function handle(User $learner, SchoolLearningAssignment $assignment, string $responseText): SchoolLearningSubmission
    {
        return DB::transaction(function () use ($learner, $assignment, $responseText): SchoolLearningSubmission {
            $profile = $learner->learnerProfile;
            abort_unless($profile !== null, 404);

            $school = School::query()->whereKey($assignment->school_id)->lockForUpdate()->firstOrFail();
            $lockedAssignment = $school->learningAssignments()
                ->whereKey($assignment->id)
                ->where('status', SchoolLearningAssignmentStatus::Published->value)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('viewForLearner', $lockedAssignment);

            $recipient = $lockedAssignment->recipients()
                ->where('learner_profile_id', $profile->id)
                ->whereHas('enrolment', fn ($query) => $query->where('status', 'active')->where('school_id', $school->id))
                ->lockForUpdate()
                ->firstOrFail();
            $submission = $recipient->submission()->lockForUpdate()->first();

            if ($submission?->status === SchoolLearningSubmissionStatus::Submitted->value) {
                return $submission;
            }

            $effectiveCutoff = $lockedAssignment->cutoff_at ?? $lockedAssignment->due_at;
            $submittedAt = now();
            if ($submittedAt->greaterThan($effectiveCutoff)) {
                throw ValidationException::withMessages(['response_text' => 'The deadline for this assignment has passed.']);
            }

            $submissionAttributes = [
                'school_id' => $school->id,
                'school_learning_assignment_id' => $lockedAssignment->id,
                'learner_profile_id' => $profile->id,
                'submitted_by_user_id' => $learner->id,
                'response_text' => $responseText,
                'status' => SchoolLearningSubmissionStatus::Submitted,
                'is_late' => $submittedAt->greaterThan($lockedAssignment->due_at),
                'submitted_at' => $submittedAt,
                'acknowledgement_reference' => (string) Str::ulid(),
            ];

            if ($submission instanceof SchoolLearningSubmission) {
                $submission->forceFill($submissionAttributes)->save();
            } else {
                $submission = $recipient->submission()->create($submissionAttributes);
            }

            $school->auditEvents()->create([
                'actor_user_id' => $learner->id,
                'event_type' => 'school_learning_submission.submitted',
                'auditable_type' => SchoolLearningSubmission::class,
                'auditable_id' => $submission->id,
                'metadata' => [
                    'assignment_id' => $lockedAssignment->id,
                    'is_late' => $submission->is_late,
                ],
                'occurred_at' => $submittedAt,
            ]);

            return $submission->refresh();
        });
    }
}
