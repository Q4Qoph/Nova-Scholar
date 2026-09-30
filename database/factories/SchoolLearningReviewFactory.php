<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SchoolLearningReview;
use App\Models\SchoolLearningSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SchoolLearningReview> */
class SchoolLearningReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'school_learning_submission_id' => SchoolLearningSubmission::factory(),
            'school_id' => fn (array $attributes): int => SchoolLearningSubmission::findOrFail($attributes['school_learning_submission_id'])->school_id,
            'feedback' => fake()->paragraph(),
            'score' => null,
            'maximum_score' => null,
            'released_at' => null,
        ];
    }
}
