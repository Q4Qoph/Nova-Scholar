<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' School',
            'slug' => fake()->unique()->slug(2),
            'school_type' => fake()->randomElement(['day', 'boarding', 'mixed']),
            'status' => 'active',
            'timezone' => 'Africa/Nairobi',
        ];
    }
}
