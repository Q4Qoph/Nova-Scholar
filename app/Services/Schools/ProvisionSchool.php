<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ProvisionSchool
{
    /**
     * @param  array{name: string, slug: string, school_type: string, timezone?: string}  $schoolData
     */
    public function handle(User $actor, array $schoolData, User $administrator): School
    {
        if ($actor->role !== UserRole::Admin || ! $actor->hasVerifiedEmail()) {
            throw new AuthorizationException('Only a verified platform administrator can provision a school.');
        }

        if (! $administrator->hasVerifiedEmail()) {
            throw new AuthorizationException('The first school administrator must have a verified email address.');
        }

        return DB::transaction(function () use ($actor, $administrator, $schoolData): School {
            $school = School::query()->create([
                'name' => $schoolData['name'],
                'slug' => $schoolData['slug'],
                'school_type' => $schoolData['school_type'],
                'status' => 'active',
                'timezone' => $schoolData['timezone'] ?? 'Africa/Nairobi',
            ]);
            $membership = $school->memberships()->create([
                'user_id' => $administrator->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school.provisioned',
                'auditable_type' => School::class,
                'auditable_id' => $school->id,
                'metadata' => ['administrator_membership_id' => $membership->id],
                'occurred_at' => now(),
            ]);

            return $school;
        });
    }
}
