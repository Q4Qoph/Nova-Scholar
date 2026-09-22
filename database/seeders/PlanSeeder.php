<?php

namespace Database\Seeders;

use App\Models\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startsAt = CarbonImmutable::create(2026, 7, 1, 0, 0, 0, 'UTC');

        foreach ([
            ['code' => 'student', 'name' => 'Student', 'amount_minor' => 29900],
            ['code' => 'pro', 'name' => 'Pro', 'amount_minor' => 69900],
        ] as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['code' => $planData['code']],
                [
                    'name' => $planData['name'],
                    'description' => 'Monthly access for individual learners.',
                    'is_active' => true,
                    'is_public' => true,
                ],
            );

            $plan->prices()->updateOrCreate(
                ['starts_at' => $startsAt],
                [
                    'amount_minor' => $planData['amount_minor'],
                    'currency' => 'KES',
                    'billing_interval' => 'month',
                    'ends_at' => null,
                ],
            );

            $plan->features()->updateOrCreate(
                ['feature_code' => 'ai_chat'],
                ['allowance' => $planData['code'] === 'student' ? 100 : 500],
            );
            foreach (['quiz_generation', 'flashcard_generation'] as $featureCode) {
                $plan->features()->updateOrCreate(
                    ['feature_code' => $featureCode],
                    ['allowance' => $planData['code'] === 'student' ? 20 : 100],
                );
            }
        }
    }
}
