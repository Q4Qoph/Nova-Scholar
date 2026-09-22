<?php

namespace Database\Factories;

use App\Models\SchoolMembership;
use App\Models\SchoolRoleAssignment;
use App\SchoolRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolRoleAssignment>
 */
class SchoolRoleAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_membership_id' => SchoolMembership::factory(),
            'role' => SchoolRole::Teacher,
        ];
    }
}
