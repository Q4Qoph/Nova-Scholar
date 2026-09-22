<?php

namespace Database\Factories;

use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeCharge>
 */
class FeeChargeFactory extends Factory
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
            'fee_charge_batch_id' => FeeChargeBatch::factory(),
            'fee_schedule_id' => FeeSchedule::factory(),
            'enrolment_id' => Enrolment::factory(),
            'description' => 'Tuition',
            'currency' => 'KES',
            'amount_minor' => 250000,
            'status' => 'posted',
            'charged_on' => today(),
        ];
    }
}
