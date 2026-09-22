<?php

namespace App\Services\Schools;

use App\Models\SchoolInvitation;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AcceptSchoolInvitation
{
    public function handle(User $actor, string $token): SchoolMembership
    {
        if (! $actor->hasVerifiedEmail()) {
            throw new AuthorizationException('Only verified users can accept school invitations.');
        }

        return DB::transaction(function () use ($actor, $token): SchoolMembership {
            $invitation = SchoolInvitation::query()
                ->lockForUpdate()
                ->where('token_hash', hash('sha256', $token))
                ->first();
            if ($invitation === null || $invitation->accepted_at !== null || $invitation->revoked_at !== null || $invitation->expires_at->isPast()) {
                throw new AuthorizationException('This invitation is invalid or no longer available.');
            }
            if ($invitation->invitee_user_id !== $actor->id || $invitation->email !== $actor->email) {
                throw new AuthorizationException('This invitation is not assigned to the signed-in user.');
            }
            if ($invitation->school->status !== 'active') {
                throw new AuthorizationException('This school is not accepting invitations.');
            }
            if (SchoolMembership::query()->active()->where('school_id', $invitation->school_id)->where('user_id', $actor->id)->exists()) {
                throw new AuthorizationException('The signed-in user already belongs to this school.');
            }

            $membership = $invitation->school->memberships()->create([
                'user_id' => $actor->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $membership->roles()->create(['role' => $invitation->role]);
            $invitation->update(['accepted_at' => now()]);
            $invitation->school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school.invitation.accepted',
                'auditable_type' => SchoolInvitation::class,
                'auditable_id' => $invitation->id,
                'metadata' => ['membership_id' => $membership->id, 'role' => $invitation->role->value],
                'occurred_at' => now(),
            ]);

            return $membership;
        });
    }
}
