<?php

namespace App\Policies;

use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class LearnerClassMembershipPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $this->hasStaffAccess($user, $school);
    }

    public function view(User $user, LearnerClassMembership $membership): bool
    {
        return $this->hasStaffAccess($user, $membership->school);
    }

    public function create(User $user, School $school): bool
    {
        return $this->hasAdminAccess($user, $school);
    }

    public function update(User $user, LearnerClassMembership $membership): bool
    {
        return false;
    }

    public function delete(User $user, LearnerClassMembership $membership): bool
    {
        return false;
    }

    public function restore(User $user, LearnerClassMembership $membership): bool
    {
        return false;
    }

    public function forceDelete(User $user, LearnerClassMembership $membership): bool
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
        return $school->status === 'active'
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
    }
}
