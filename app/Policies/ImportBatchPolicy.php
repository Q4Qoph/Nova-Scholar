<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;

class ImportBatchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, School $school): bool
    {
        return $this->hasAdminAccess($user, $school);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $this->hasAdminAccess($user, $importBatch->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, School $school): bool
    {
        return $this->hasAdminAccess($user, $school);
    }

    public function commit(User $user, ImportBatch $importBatch): bool
    {
        return $this->hasAdminAccess($user, $importBatch->school);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImportBatch $importBatch): bool
    {
        return false;
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
