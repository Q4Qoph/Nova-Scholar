<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolMembership>
 */
class SchoolMembershipFactory extends Factory
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
            'user_id' => User::factory(),
            'status' => 'active',
            'joined_at' => now(),
            'removed_at' => null,
        ];
    }
}
