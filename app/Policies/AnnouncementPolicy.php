<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class AnnouncementPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $this->isSchoolAdmin($user, $school);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $this->isSchoolAdmin($user, $announcement->school);
    }

    public function create(User $user, School $school): bool
    {
        return $this->isSchoolAdmin($user, $school);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $announcement->status === 'draft' && $this->isSchoolAdmin($user, $announcement->school);
    }

    public function send(User $user, Announcement $announcement): bool
    {
        return in_array($announcement->status, ['draft', 'sent'], true)
            && $this->isSchoolAdmin($user, $announcement->school);
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
