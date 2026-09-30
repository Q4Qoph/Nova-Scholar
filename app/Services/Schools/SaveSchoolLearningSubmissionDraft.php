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
use Illuminate\Validation\ValidationException;

class SaveSchoolLearningSubmissionDraft
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
            $this->ensureBeforeCutoff($lockedAssignment);

            $submission = $recipient->submission()->lockForUpdate()->first();
            if ($submission?->status === SchoolLearningSubmissionStatus::Submitted->value) {
                throw ValidationException::withMessages(['response_text' => 'This assignment has already been submitted.']);
            }

            if ($submission instanceof SchoolLearningSubmission) {
                $submission->forceFill([
                    'response_text' => $responseText,
                    'status' => SchoolLearningSubmissionStatus::Draft,
                    'draft_saved_at' => now(),
                ])->save();
            } else {
                $submission = $recipient->submission()->create([
                    'school_id' => $school->id,
                    'school_learning_assignment_id' => $lockedAssignment->id,
                    'learner_profile_id' => $profile->id,
                    'response_text' => $responseText,
                    'status' => SchoolLearningSubmissionStatus::Draft,
                    'draft_saved_at' => now(),
                ]);
            }

            $school->auditEvents()->create([
                'actor_user_id' => $learner->id,
                'event_type' => 'school_learning_submission.draft_saved',
                'auditable_type' => SchoolLearningSubmission::class,
                'auditable_id' => $submission->id,
                'metadata' => ['assignment_id' => $lockedAssignment->id],
                'occurred_at' => now(),
            ]);

            return $submission->refresh();
        });
    }

    private function ensureBeforeCutoff(SchoolLearningAssignment $assignment): void
    {
        $effectiveCutoff = $assignment->cutoff_at ?? $assignment->due_at;

        if (now()->greaterThan($effectiveCutoff)) {
            throw ValidationException::withMessages(['response_text' => 'The deadline for this assignment has passed.']);
        }
    }
}
