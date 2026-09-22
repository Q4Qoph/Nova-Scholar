<?php

namespace Database\Factories;

use App\Models\Enrolment;
use App\Models\LearnerProfile;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrolment>
 */
class EnrolmentFactory extends Factory
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
            'admission_number' => fake()->unique()->numerify('ADM-#####'),
            'status' => 'active',
            'enrolled_at' => today(),
            'withdrawn_at' => null,
        ];
    }
}
