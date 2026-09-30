<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;

class SchoolLearningAssignmentPolicy
{
    public function createForAssignment(User $user, TeachingAssignment $assignment): bool
    {
        return $assignment->school->status === 'active'
            && $assignment->status === 'active'
            && $assignment->classGroup->status === 'active'
            && ($assignment->teacher_user_id === $user->id
                ? $this->hasRole($user, $assignment->school, [SchoolRole::Teacher])
                : $this->hasRole($user, $assignment->school, [SchoolRole::SchoolAdmin]));
    }

    public function view(User $user, SchoolLearningAssignment $assignment): bool
    {
        return $assignment->school_id === $assignment->teachingAssignment->school_id
            && $assignment->status !== 'withdrawn'
            && $this->canManageAssignment($user, $assignment->teachingAssignment);
    }

    public function update(User $user, SchoolLearningAssignment $assignment): bool
    {
        return $assignment->status === 'draft' && $this->view($user, $assignment);
    }

    public function viewForLearner(User $user, SchoolLearningAssignment $assignment): bool
    {
        $profile = $user->learnerProfile;

        if ($assignment->status !== 'published'
            || $assignment->school->status !== 'active'
            || $profile === null
            || $profile->status !== 'active'
            || ! $user->isActiveManagedLearner()) {
            return false;
        }

        return $assignment->recipients()
            ->where('learner_profile_id', $profile->id)
            ->where('school_id', $assignment->school_id)
            ->whereHas('enrolment', fn ($query) => $query
                ->where('school_id', $assignment->school_id)
                ->where('learner_profile_id', $profile->id)
                ->where('status', 'active'))
            ->exists();
    }

    private function canManageAssignment(User $user, TeachingAssignment $assignment): bool
    {
        return $assignment->status === 'active'
            && $assignment->classGroup->status === 'active'
            && $this->hasRole($user, $assignment->school, [SchoolRole::Teacher, SchoolRole::SchoolAdmin])
            && ($assignment->teacher_user_id === $user->id
                || $this->hasRole($user, $assignment->school, [SchoolRole::SchoolAdmin]));
    }

    /** @param array<int, SchoolRole> $roles */
    private function hasRole(User $user, School $school, array $roles): bool
    {
        if ($school->status !== 'active') {
            return false;
        }

        $roleValues = array_map(fn (SchoolRole $role): string => $role->value, $roles);

        return $user->schoolMemberships()
            ->active()
            ->where('school_id', $school->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('role', $roleValues))
            ->exists();
    }
}
