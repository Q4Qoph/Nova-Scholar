<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\SchoolLearningAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildLearnerTaskSummary
{
    public function assignments(User $user): Builder
    {
        $profile = $user->learnerProfile;

        return SchoolLearningAssignment::query()
            ->where('status', 'published')
            ->when(! $user->isActiveManagedLearner() || $profile?->status !== 'active', fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->whereHas('school', fn (Builder $query): Builder => $query->where('status', 'active'))
            ->whereHas('recipients', fn (Builder $query): Builder => $query
                ->where('learner_profile_id', $profile?->id)
                ->whereColumn('school_learning_assignment_recipients.school_id', 'school_learning_assignments.school_id')
                ->whereHas('enrolment', fn (Builder $enrolment): Builder => $enrolment
                    ->where('learner_profile_id', $profile?->id)->where('status', 'active')
                    ->whereColumn('enrolments.school_id', 'school_learning_assignment_recipients.school_id')));
    }

    /** @return array{needed: int, closed: int, feedback: int} */
    public function handle(User $user): array
    {
        $profileId = $user->learnerProfile?->id;
        $unsubmitted = $this->assignments($user)->whereDoesntHave('submissions', fn (Builder $query): Builder => $query
            ->where('school_learning_submissions.learner_profile_id', $profileId)->where('school_learning_submissions.status', 'submitted'));

        return [
            'needed' => (clone $unsubmitted)->whereRaw('COALESCE(cutoff_at, due_at) >= ?', [now()])->count(),
            'closed' => (clone $unsubmitted)->whereRaw('COALESCE(cutoff_at, due_at) < ?', [now()])->count(),
            'feedback' => $this->assignments($user)->whereHas('submissions', fn (Builder $query): Builder => $query
                ->where('school_learning_submissions.learner_profile_id', $profileId)->where('school_learning_submissions.status', 'submitted')
                ->whereColumn('school_learning_submissions.school_id', 'school_learning_assignments.school_id')
                ->whereHas('releasedReview', fn (Builder $review): Builder => $review->whereColumn('school_learning_reviews.school_id', 'school_learning_submissions.school_id')))->count(),
        ];
    }
}
