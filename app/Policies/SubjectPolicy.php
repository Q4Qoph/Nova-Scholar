<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\SchoolRole;

class SubjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, School $school): bool
    {
        return $this->hasStaffAccess($user, $school);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subject $subject): bool
    {
        return $subject->status === 'active' && $this->hasStaffAccess($user, $subject->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, School $school): bool
    {
        return $this->hasAdminAccess($user, $school);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subject $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subject $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Subject $subject): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Subject $subject): bool
    {
        return false;
    }

    private function hasStaffAccess(User $user, School $school): bool
    {
        return $school->status === 'active'
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->whereIn('role', [
                    SchoolRole::SchoolAdmin->value,
                    SchoolRole::Teacher->value,
                    SchoolRole::Bursar->value,
                ]))
                ->exists();
    }

    private function hasAdminAccess(User $user, School $school): bool
    {
        return $this->hasStaffAccess($user, $school)
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
    }
}
