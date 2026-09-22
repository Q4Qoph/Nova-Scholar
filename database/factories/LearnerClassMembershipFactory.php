<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearnerClassMembership>
 */
class LearnerClassMembershipFactory extends Factory
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
            'class_group_id' => ClassGroup::factory(),
            'starts_on' => today(),
            'ends_on' => null,
            'status' => 'active',
        ];
    }
}
