<?php

namespace App\Services\Schools;

use App\Models\GuardianLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeGuardianLink
{
    public function handle(User $actor, GuardianLink $guardianLink): void
    {
        if ($guardianLink->status !== 'active' || $guardianLink->revoked_at !== null) {
            return;
        }

        DB::transaction(function () use ($actor, $guardianLink): void {
            $guardianLink->update(['status' => 'revoked', 'revoked_at' => now()]);
            $guardianLink->school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'guardian.link.revoked',
                'auditable_type' => GuardianLink::class,
                'auditable_id' => $guardianLink->id,
                'metadata' => ['enrolment_id' => $guardianLink->enrolment_id, 'guardian_user_id' => $guardianLink->guardian_user_id],
                'occurred_at' => now(),
            ]);
        });
    }
}
