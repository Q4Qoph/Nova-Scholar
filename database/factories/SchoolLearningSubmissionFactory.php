<?php

namespace Database\Factories;

use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentRecipient;
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLearningSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLearningSubmission>
 */
class SchoolLearningSubmissionFactory extends Factory
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
            'school_learning_assignment_recipient_id' => SchoolLearningAssignmentRecipient::factory(),
            'learner_profile_id' => LearnerProfile::factory(),
            'submitted_by_user_id' => null,
            'response_text' => fake()->paragraph(),
            'status' => SchoolLearningSubmissionStatus::Draft->value,
            'is_late' => false,
            'draft_saved_at' => now(),
            'submitted_at' => null,
            'acknowledgement_reference' => null,
        ];
    }
}
