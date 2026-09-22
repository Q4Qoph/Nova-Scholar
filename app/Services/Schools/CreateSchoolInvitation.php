<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\SchoolMembership;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSchoolInvitation
{
    /**
     * @return array{invitation: SchoolInvitation, token: string}
     */
    public function handle(User $actor, School $school, string $email, SchoolRole $role): array
    {
        if (! $role->isStaffRole()) {
            throw new AuthorizationException('This role cannot be granted through a staff invitation.');
        }

        $this->ensureSchoolAdmin($actor, $school);
        $invitee = User::query()->where('email', $email)->first();
        if ($invitee === null || ! $invitee->hasVerifiedEmail()) {
            throw new AuthorizationException('Invitations can only target existing verified users.');
        }
        if (SchoolMembership::query()->active()->where('school_id', $school->id)->where('user_id', $invitee->id)->exists()) {
            throw new AuthorizationException('This user already belongs to the school.');
        }

        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($actor, $email, $invitee, $role, $school, $token): SchoolInvitation {
            $invitation = $school->invitations()->create([
                'inviter_user_id' => $actor->id,
                'invitee_user_id' => $invitee->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school.invitation.created',
                'auditable_type' => SchoolInvitation::class,
                'auditable_id' => $invitation->id,
                'metadata' => ['invitee_user_id' => $invitee->id, 'role' => $role->value],
                'occurred_at' => now(),
            ]);

            return $invitation;
        });

        return ['invitation' => $invitation, 'token' => $token];
    }

    public function revoke(User $actor, SchoolInvitation $invitation): void
    {
        $this->ensureSchoolAdmin($actor, $invitation->school);
        if ($invitation->accepted_at !== null || $invitation->revoked_at !== null) {
            return;
        }

        DB::transaction(function () use ($actor, $invitation): void {
            $invitation->update(['revoked_at' => now()]);
            $invitation->school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'school.invitation.revoked',
                'auditable_type' => SchoolInvitation::class,
                'auditable_id' => $invitation->id,
                'metadata' => ['invitee_user_id' => $invitation->invitee_user_id],
                'occurred_at' => now(),
            ]);
        });
    }

    private function ensureSchoolAdmin(User $actor, School $school): void
    {
        $authorized = SchoolMembership::query()
            ->active()
            ->where('school_id', $school->id)
            ->where('user_id', $actor->id)
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();
        if (! $authorized) {
            throw new AuthorizationException('Only an active school administrator can manage invitations.');
        }
    }
}
