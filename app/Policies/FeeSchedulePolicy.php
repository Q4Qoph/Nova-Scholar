<?php

namespace App\Policies;

use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class FeeSchedulePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $this->isSchoolAdmin($user, $school);
    }

    public function create(User $user, School $school): bool
    {
        return $this->isSchoolAdmin($user, $school);
    }

    public function post(User $user, FeeSchedule $feeSchedule): bool
    {
        return $this->isSchoolAdmin($user, $feeSchedule->school);
    }

    private function isSchoolAdmin(User $user, School $school): bool
    {
        return $school->status === 'active'
            && $user->schoolMemberships()
                ->active()
                ->where('school_id', $school->id)
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->exists();
    }
}
