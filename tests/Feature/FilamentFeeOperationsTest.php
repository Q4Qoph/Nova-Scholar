<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentFeeOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_tenant_scoped_fee_operations(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'fee-operations-school', 'name' => 'Fee Operations School']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $schedule = FeeSchedule::factory()->for($school)->create(['name' => 'Term Tuition']);
        $batch = FeeChargeBatch::factory()->for($school)->for($schedule)->create([
            'status' => 'posted',
            'eligible_count' => 1,
            'total_minor' => 250000,
            'posted_at' => now(),
        ]);
        $learner = Enrolment::factory()->for($school)->create();
        FeeCharge::factory()->for($school)->for($schedule)->for($batch, 'feeChargeBatch')->for($learner)->create();

        $this->actingAs($admin)
            ->get('/school/fee-operations-school/fee-operations')
            ->assertOk()
            ->assertSee('Fee Operations School')
            ->assertSee('Term Tuition')
            ->assertSee($batch->batch_key)
            ->assertSee($learner->learnerProfile->first_name);
    }

    public function test_teacher_cannot_view_fee_operations(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-fee-operations-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher)
            ->get('/school/blocked-fee-operations-school/fee-operations')
            ->assertForbidden();
    }
}
