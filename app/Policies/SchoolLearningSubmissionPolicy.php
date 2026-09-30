<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolLearningSubmission;
use App\Models\User;

class SchoolLearningSubmissionPolicy
{
    public function review(User $user, SchoolLearningSubmission $submission): bool
    {
        $assignment = $submission->assignment;
        $recipient = $submission->recipient;

        return $submission->status === 'submitted'
            && $assignment->status === 'published'
            && $submission->school_id === $assignment->school_id
            && $recipient->school_id === $assignment->school_id
            && $recipient->school_learning_assignment_id === $assignment->id
            && $recipient->learner_profile_id === $submission->learner_profile_id
            && (new SchoolLearningAssignmentPolicy)->view($user, $assignment);
    }
}
