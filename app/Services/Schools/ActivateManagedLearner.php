<?php

namespace App\Services\Schools;

use App\Models\LearnerActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ActivateManagedLearner
{
    public function handle(string $token, string $password): User
    {
        return DB::transaction(function () use ($token, $password): User {
            $activation = LearnerActivation::query()
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->with('learnerProfile.user')
                ->lockForUpdate()
                ->first();

            $user = $activation?->learnerProfile?->user;
            if ($activation === null || ! $user instanceof User || ! $user->isManagedLearner()) {
                throw ValidationException::withMessages(['token' => 'This activation link is invalid or has expired.']);
            }

            $user->forceFill([
                'password' => Hash::make($password),
                'learner_activated_at' => now(),
            ])->save();
            $activation->update(['used_at' => now()]);

            return $user->fresh();
        });
    }
}
