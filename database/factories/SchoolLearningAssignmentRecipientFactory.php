<?php

namespace Database\Factories;

use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLearningAssignmentRecipient>
 */
class SchoolLearningAssignmentRecipientFactory extends Factory
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
            'school_learning_assignment_id' => SchoolLearningAssignment::factory(),
            'learner_profile_id' => LearnerProfile::factory(),
            'enrolment_id' => Enrolment::factory(),
            'learner_class_membership_id' => LearnerClassMembership::factory(),
            'assigned_at' => now(),
        ];
    }
}
