<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolLearningReview;
use App\Models\SchoolLearningSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReleaseSchoolLearningReview
{
    public function handle(User $actor, School $school, SchoolLearningSubmission $submission): SchoolLearningReview
    {
        return DB::transaction(function () use ($actor, $school, $submission): SchoolLearningReview {
            School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $assignment = $school->learningAssignments()->whereKey($submission->school_learning_assignment_id)->lockForUpdate()->firstOrFail();
            $lockedSubmission = $assignment->submissions()->where('school_learning_submissions.school_id', $school->id)
                ->where('school_learning_submissions.id', $submission->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('review', $lockedSubmission);
            $review = $lockedSubmission->review()->where('school_id', $school->id)->lockForUpdate()->first();
            if ($review === null) {
                throw ValidationException::withMessages(['reviewFeedback' => 'Save feedback before releasing it.']);
            }
            if ($review->released_at !== null) {
                return $review;
            }

            $review->forceFill(['released_by_user_id' => $actor->id, 'released_at' => now()])->save();
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school_learning_review.released',
                'auditable_type' => SchoolLearningReview::class,
                'auditable_id' => $review->id,
                'metadata' => ['submission_id' => $lockedSubmission->id],
                'occurred_at' => now(),
            ]);

            return $review;
        });
    }
}
