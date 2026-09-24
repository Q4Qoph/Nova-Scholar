<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdmitLearner
{
    /**
     * @param  array{first_name: string, last_name: string, preferred_name?: string|null, date_of_birth?: string|null, admission_number: string}  $data
     */
    public function handle(User $actor, School $school, array $data): Enrolment
    {
        return DB::transaction(function () use ($actor, $school, $data): Enrolment {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $profile = LearnerProfile::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'preferred_name' => $data['preferred_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'status' => 'active',
            ]);

            $enrolment = $school->enrolments()->create([
                'learner_profile_id' => $profile->id,
                'admission_number' => $data['admission_number'],
                'status' => 'active',
                'enrolled_at' => today(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'learner.admitted',
                'auditable_type' => Enrolment::class,
                'auditable_id' => $enrolment->id,
                'metadata' => [
                    'learner_profile_id' => $profile->id,
                    'admission_number' => $enrolment->admission_number,
                ],
                'occurred_at' => now(),
            ]);

            return $enrolment;
        });
    }
}
