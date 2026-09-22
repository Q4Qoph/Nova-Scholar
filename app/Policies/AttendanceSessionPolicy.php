<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class AttendanceSessionPolicy
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
    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->canManageSession($user, $attendanceSession);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, School $school): bool
    {
        return $this->hasStaffAccess($user, $school);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $this->canManageSession($user, $attendanceSession);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceSession $attendanceSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AttendanceSession $attendanceSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AttendanceSession $attendanceSession): bool
    {
        return false;
    }

    private function canManageSession(User $user, AttendanceSession $attendanceSession): bool
    {
        if (! $this->hasStaffAccess($user, $attendanceSession->school)) {
            return false;
        }

        return $this->isAdmin($user, $attendanceSession->school)
            || $attendanceSession->teachingAssignment->teacher_user_id === $user->id;
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
                ]))
                ->exists();
    }

    private function isAdmin(User $user, School $school): bool
    {
        return $user->schoolMemberships()
            ->active()
            ->where('school_id', $school->id)
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();
    }
}
