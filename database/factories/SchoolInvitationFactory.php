<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolInvitation>
 */
class SchoolInvitationFactory extends Factory
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
            'inviter_user_id' => User::factory(),
            'invitee_user_id' => User::factory(),
            'email' => fake()->safeEmail(),
            'role' => SchoolRole::Teacher,
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'revoked_at' => null,
        ];
    }
}
