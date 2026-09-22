<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
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
            'actor_user_id' => User::factory(),
            'event_type' => 'school.created',
            'auditable_type' => School::class,
            'auditable_id' => null,
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
