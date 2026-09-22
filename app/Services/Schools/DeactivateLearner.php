<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\School;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeactivateLearner
{
    public function handle(User $actor, School $school, Enrolment $enrolment, string $deactivatedOn): Enrolment
    {
        return DB::transaction(function () use ($actor, $school, $enrolment, $deactivatedOn): Enrolment {
            $lockedEnrolment = Enrolment::query()->with('learnerProfile.user')->whereKey($enrolment->id)->where('school_id', $school->id)->where('status', 'active')->lockForUpdate()->first();

            if ($lockedEnrolment === null) {
                throw new AuthorizationException('The learner is not active in this school.');
            }

            $date = CarbonImmutable::parse($deactivatedOn);
            $lockedEnrolment->update(['status' => 'withdrawn', 'withdrawn_at' => $date->toDateString()]);
            $lockedEnrolment->classMemberships()
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $date)
                ->where(function ($query) use ($date): void {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date);
                })
                ->update(['ends_on' => $date->subDay()->toDateString()]);
            $lockedEnrolment->learnerProfile->update(['status' => 'inactive']);
            $managedLearner = $lockedEnrolment->learnerProfile->user?->isManagedLearner() ?? false;
            if ($managedLearner) {
                $lockedEnrolment->learnerProfile->user->update(['learner_deactivated_at' => now()]);
            }

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'learner.deactivated',
                'auditable_type' => Enrolment::class,
                'auditable_id' => $lockedEnrolment->id,
                'metadata' => ['learner_profile_id' => $lockedEnrolment->learner_profile_id, 'deactivated_on' => $date->toDateString(), 'managed_access_deactivated' => $managedLearner],
                'occurred_at' => now(),
            ]);

            return $lockedEnrolment;
        });
    }
}
