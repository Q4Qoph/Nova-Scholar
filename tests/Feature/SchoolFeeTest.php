<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\FeeSchedule;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_preview_post_and_retry_an_idempotent_class_charge_batch(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $classGroup = $this->classGroup($school);
        $firstLearner = $this->placeLearner($school, $classGroup, 'F-001');
        $secondLearner = $this->placeLearner($school, $classGroup, 'F-002');
        Enrolment::factory()->for($school)->create(['admission_number' => 'F-003']);

        $this->actingAs($admin)->post(route('schools.fee-schedules.store', $school), [
            'name' => 'Grade 5 tuition',
            'currency' => 'KES',
            'amount_minor' => 125000,
            'class_group_id' => $classGroup->id,
        ])->assertRedirectToRoute('schools.fees.index', $school);
        $schedule = FeeSchedule::query()->firstOrFail();
        $batchPayload = ['fee_schedule_id' => $schedule->id, 'batch_key' => 'grade-5-term-1'];

        $this->actingAs($admin)
            ->post(route('schools.fee-charge-batches.preview', $school), $batchPayload)
            ->assertOk()
            ->assertSee('2 active enrolments')
            ->assertSee('125,000');

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $batchPayload)->assertRedirect();
        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $batchPayload)->assertRedirect();

        $this->assertDatabaseCount('fee_charges', 2);
        $this->assertDatabaseHas('fee_charges', ['enrolment_id' => $firstLearner->id, 'amount_minor' => 125000, 'status' => 'posted']);
        $this->assertDatabaseHas('fee_charges', ['enrolment_id' => $secondLearner->id, 'amount_minor' => 125000, 'status' => 'posted']);
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_posted_charge_keeps_its_snapshot_when_schedule_changes(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $schedule = FeeSchedule::factory()->for($school)->create(['amount_minor' => 90000]);
        Enrolment::factory()->for($school)->create();
        $payload = ['fee_schedule_id' => $schedule->id, 'batch_key' => 'snapshot-1'];

        $this->actingAs($admin)->post(route('schools.fee-charge-batches.post', $school), $payload)->assertRedirect();
        $schedule->update(['amount_minor' => 110000]);

        $this->assertDatabaseHas('fee_charges', ['fee_schedule_id' => $schedule->id, 'amount_minor' => 90000]);
    }

    public function test_only_school_admins_can_manage_fee_schedules_and_batches(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);

        $this->actingAs($teacher)->get(route('schools.fees.index', $school))->assertForbidden();
        $this->actingAs($teacher)->post(route('schools.fee-schedules.store', $school), [
            'name' => 'Blocked',
            'currency' => 'KES',
            'amount_minor' => 100,
        ])->assertForbidden();
    }

    /** @return array{0: User, 1: School} */
    private function schoolContext(SchoolRole $role): array
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => $role]);

        return [$user, $school];
    }

    private function classGroup(School $school): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);

        return ClassGroup::factory()->for($school)->for($academicYear)->create();
    }

    private function placeLearner(School $school, ClassGroup $classGroup, string $admissionNumber): Enrolment
    {
        $enrolment = Enrolment::factory()->for($school)->create(['admission_number' => $admissionNumber]);
        LearnerClassMembership::factory()->create(['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'class_group_id' => $classGroup->id, 'starts_on' => '2026-01-01']);

        return $enrolment;
    }
}
