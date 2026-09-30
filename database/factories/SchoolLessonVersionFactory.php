<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SchoolLesson;
use App\Models\SchoolLessonVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLessonVersion>
 */
class SchoolLessonVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_lesson_id' => SchoolLesson::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'status' => 'draft',
            'created_by_user_id' => User::factory(),
            'published_at' => null,
            'withdrawn_at' => null,
        ];
    }
}
