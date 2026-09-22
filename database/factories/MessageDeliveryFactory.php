<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\MessageDelivery;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageDelivery>
 */
class MessageDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'school_id' => School::factory(),
            'guardian_user_id' => User::factory(),
            'channel' => 'in_app',
            'status' => 'sent',
            'delivered_at' => now(),
            'read_at' => null,
        ];
    }
}
