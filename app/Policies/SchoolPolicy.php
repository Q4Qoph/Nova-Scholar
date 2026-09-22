<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, School $school): bool
    {
        return $school->status === 'active'
            && $user->schoolMemberships()->active()->where('school_id', $school->id)->exists();
    }
}
