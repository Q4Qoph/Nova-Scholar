<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\LearnerActivation;
use App\Models\School;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateManagedLearnerAccess
{
    /**
     * @return array{user: User, activation: LearnerActivation, token: string}
     */
    public function handle(User $actor, School $school, Enrolment $enrolment): array
    {
        return DB::transaction(function () use ($actor, $school, $enrolment): array {
            $lockedEnrolment = Enrolment::query()
                ->whereKey($enrolment->id)
                ->where('school_id', $school->id)
                ->with('learnerProfile')
                ->lockForUpdate()
                ->first();

            if ($lockedEnrolment === null) {
                throw new AuthorizationException('The learner is not part of this school.');
            }

            $profile = $lockedEnrolment->learnerProfile;
            if ($profile->user_id !== null) {
                throw ValidationException::withMessages(['access' => 'This learner already has managed access.']);
            }

            $loginId = $this->generateLoginId();
            $user = User::query()->create([
                'name' => $profile->preferred_name ?: $profile->first_name.' '.$profile->last_name,
                'email' => null,
                'password' => Hash::make(Str::random(64)),
                'account_type' => 'managed_learner',
                'learner_login_id' => $loginId,
                'learner_activated_at' => null,
                'role' => UserRole::Student,
                'learning_preferences' => [
                    'preferred_subjects' => [],
                    'daily_study_goal_minutes' => 30,
                    'timezone' => $school->timezone,
                ],
            ]);

            $profile->update(['user_id' => $user->id]);
            $token = Str::random(64);
            $activation = $school->learnerActivations()->create([
                'learner_profile_id' => $profile->id,
                'issued_by_user_id' => $actor->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDay(),
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'managed_learner_access.issued',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => ['learner_profile_id' => $profile->id, 'learner_login_id' => $loginId, 'activation_id' => $activation->id],
                'occurred_at' => now(),
            ]);

            return compact('user', 'activation', 'token');
        });
    }

    private function generateLoginId(): string
    {
        do {
            $loginId = 'NSL-'.Str::upper(Str::random(8));
        } while (User::query()->where('learner_login_id', $loginId)->exists());

        return $loginId;
    }
}
