<?php

namespace App\Services\Schools;

use App\Models\SchoolMembership;
use App\Models\SchoolRoleAssignment;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ManageSchoolRole
{
    public function assign(User $actor, SchoolMembership $membership, SchoolRole $role): SchoolRoleAssignment
    {
        $this->ensureSchoolAdmin($actor, $membership);
        if (! $role->isStaffRole()) {
            throw new AuthorizationException('This role cannot be assigned to a staff membership.');
        }
        if ($membership->status !== 'active' || $membership->removed_at !== null) {
            throw new AuthorizationException('Roles cannot be assigned to a removed membership.');
        }

        $assignment = $membership->roles()->firstOrCreate(['role' => $role]);
        if ($assignment->wasRecentlyCreated) {
            $this->audit($actor, $membership, 'school.role.assigned', ['role' => $role->value]);
        }

        return $assignment;
    }

    public function remove(User $actor, SchoolMembership $membership, SchoolRole $role): void
    {
        $this->ensureSchoolAdmin($actor, $membership);
        $assignment = $membership->roles()->where('role', $role->value)->first();
        if ($assignment === null) {
            return;
        }
        if ($role === SchoolRole::SchoolAdmin && $this->activeSchoolAdminCount($membership) <= 1) {
            throw new AuthorizationException('The last active school administrator cannot be removed.');
        }

        $assignment->delete();
        $this->audit($actor, $membership, 'school.role.removed', ['role' => $role->value]);
    }

    public function removeMembership(User $actor, SchoolMembership $membership): void
    {
        $this->ensureSchoolAdmin($actor, $membership);
        if ($membership->status !== 'active' || $membership->removed_at !== null) {
            return;
        }
        if ($membership->roles()->where('role', SchoolRole::SchoolAdmin->value)->exists() && $this->activeSchoolAdminCount($membership) <= 1) {
            throw new AuthorizationException('The last active school administrator cannot be removed.');
        }

        DB::transaction(function () use ($actor, $membership): void {
            $membership->update(['status' => 'removed', 'removed_at' => now()]);
            $this->audit($actor, $membership, 'school.membership.removed');
        });
    }

    private function ensureSchoolAdmin(User $actor, SchoolMembership $membership): void
    {
        $authorized = SchoolMembership::query()
            ->active()
            ->where('school_id', $membership->school_id)
            ->where('user_id', $actor->id)
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();
        if (! $authorized) {
            throw new AuthorizationException('Only an active school administrator can manage school roles.');
        }
    }

    private function activeSchoolAdminCount(SchoolMembership $membership): int
    {
        return SchoolRoleAssignment::query()
            ->where('role', SchoolRole::SchoolAdmin->value)
            ->whereHas('membership', fn ($query) => $query->active()->where('school_id', $membership->school_id))
            ->count();
    }

    /**
     * @param  array<string, scalar>  $metadata
     */
    private function audit(User $actor, SchoolMembership $membership, string $eventType, array $metadata = []): void
    {
        $membership->school->auditEvents()->create([
            'actor_user_id' => $actor->id,
            'event_type' => $eventType,
            'auditable_type' => SchoolMembership::class,
            'auditable_id' => $membership->id,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
