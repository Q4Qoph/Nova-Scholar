<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'class_group_id' => null,
            'audience_type' => 'all_guardians',
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => 'draft',
            'sent_at' => null,
        ];
    }
}
