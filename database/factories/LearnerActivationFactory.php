<?php

namespace Database\Factories;

use App\Models\LearnerActivation;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerActivation>
 */
class LearnerActivationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'learner_profile_id' => LearnerProfile::factory(),
            'issued_by_user_id' => User::factory(),
            'token_hash' => fake()->unique()->sha256(),
            'expires_at' => now()->addDay(),
            'used_at' => null,
            'revoked_at' => null,
        ];
    }
}
