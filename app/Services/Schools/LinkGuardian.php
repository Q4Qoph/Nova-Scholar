<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class LinkGuardian
{
    public function handle(User $actor, School $school, Enrolment $enrolment, string $email, string $relationship): GuardianLink
    {
        if ($enrolment->school_id !== $school->id || $enrolment->status !== 'active') {
            throw new AuthorizationException('The learner does not belong to this school.');
        }

        $guardian = User::query()->where('email', $email)->whereNotNull('email_verified_at')->first();
        if (! $guardian instanceof User) {
            throw new AuthorizationException('The guardian must be an existing verified user.');
        }

        return DB::transaction(function () use ($actor, $school, $enrolment, $guardian, $relationship): GuardianLink {
            $link = GuardianLink::query()->updateOrCreate(
                [
                    'enrolment_id' => $enrolment->id,
                    'guardian_user_id' => $guardian->id,
                ],
                [
                    'school_id' => $school->id,
                    'relationship' => $relationship,
                    'status' => 'active',
                    'verified_by_user_id' => $actor->id,
                    'verified_at' => now(),
                    'revoked_at' => null,
                ],
            );

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'guardian.linked',
                'auditable_type' => GuardianLink::class,
                'auditable_id' => $link->id,
                'metadata' => ['enrolment_id' => $enrolment->id, 'guardian_user_id' => $guardian->id, 'relationship' => $relationship],
                'occurred_at' => now(),
            ]);

            return $link;
        });
    }
}
