<?php

namespace Database\Factories;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuardianLink>
 */
class GuardianLinkFactory extends Factory
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
            'enrolment_id' => Enrolment::factory(),
            'guardian_user_id' => User::factory(),
            'relationship' => fake()->randomElement(['parent', 'guardian', 'caregiver']),
            'status' => 'active',
            'verified_by_user_id' => User::factory(),
            'verified_at' => now(),
            'revoked_at' => null,
        ];
    }
}
