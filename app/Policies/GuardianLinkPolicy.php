<?php

namespace App\Policies;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class GuardianLinkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function create(User $user, School $school, Enrolment $enrolment): bool
    {
        return $enrolment->school_id === $school->id
            && $enrolment->status === 'active'
            && $school->status === 'active'
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GuardianLink $guardianLink): bool
    {
        return $guardianLink->status === 'active'
            && $guardianLink->revoked_at === null
            && $guardianLink->guardian_user_id === $user->id
            && $guardianLink->enrolment()->where('status', 'active')->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function delete(User $user, GuardianLink $guardianLink): bool
    {
        return $guardianLink->school->status === 'active'
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $guardianLink->school_id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
    }
}
