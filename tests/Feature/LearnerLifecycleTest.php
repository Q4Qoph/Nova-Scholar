<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearnerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_promote_a_learner_while_preserving_placement_history(): void
    {
        [$admin, $school] = $this->schoolContext();
        $learner = Enrolment::factory()->for($school)->create();
        $currentClass = $this->classGroup($school, 'Grade 5');
        $nextClass = $this->classGroup($school, 'Grade 6');
        $currentPlacement = LearnerClassMembership::factory()->for($school)->for($learner)->for($currentClass)->create(['starts_on' => '2026-01-01', 'ends_on' => null]);

        $response = $this->actingAs($admin)->post(route('schools.learners.promote', [$school, $learner]), ['class_group_id' => $nextClass->id, 'starts_on' => '2026-06-01']);

        $response->assertRedirectToRoute('schools.learners.show', [$school, $learner]);
        $this->assertSame('2026-05-31', $currentPlacement->fresh()->ends_on->toDateString());
        $promotedPlacement = LearnerClassMembership::query()->where('enrolment_id', $learner->id)->where('class_group_id', $nextClass->id)->firstOrFail();
        $this->assertSame('2026-06-01', $promotedPlacement->starts_on->toDateString());
        $this->assertNull($promotedPlacement->ends_on);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'learner.promoted']);
    }

    public function test_dual_school_admin_can_transfer_a_learner_and_retain_source_history(): void
    {
        [$admin, $source] = $this->schoolContext();
        $destination = School::factory()->create();
        $destinationMembership = $destination->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $destinationMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $learner = Enrolment::factory()->for($source)->create();
        $sourceClass = $this->classGroup($source, 'Source class');
        $sourcePlacement = LearnerClassMembership::factory()->for($source)->for($learner)->for($sourceClass)->create(['starts_on' => '2026-01-01', 'ends_on' => null]);

        $response = $this->actingAs($admin)->post(route('schools.learners.transfer', [$source, $learner]), ['destination_school_id' => $destination->id, 'admission_number' => 'DST-1001', 'transferred_on' => '2026-07-01']);

        $response->assertRedirectToRoute('schools.learners.show', [$source, $learner]);
        $this->assertSame('withdrawn', $learner->fresh()->status);
        $this->assertSame('2026-07-01', $learner->fresh()->withdrawn_at->toDateString());
        $destinationEnrolment = Enrolment::query()->where('school_id', $destination->id)->firstOrFail();
        $this->assertSame($learner->learner_profile_id, $destinationEnrolment->learner_profile_id);
        $this->assertSame('DST-1001', $destinationEnrolment->admission_number);
        $this->assertSame('2026-06-30', $sourcePlacement->fresh()->ends_on->toDateString());
        $this->assertDatabaseHas('audit_events', ['school_id' => $source->id, 'event_type' => 'learner.transferred_out']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $destination->id, 'event_type' => 'learner.transferred_in']);
    }

    public function test_source_only_admin_cannot_transfer_into_another_school(): void
    {
        [$admin, $source] = $this->schoolContext();
        $destination = School::factory()->create();
        $learner = Enrolment::factory()->for($source)->create();

        $this->actingAs($admin)->post(route('schools.learners.transfer', [$source, $learner]), ['destination_school_id' => $destination->id, 'admission_number' => 'DST-1001', 'transferred_on' => '2026-07-01'])->assertForbidden();

        $this->assertSame('active', $learner->fresh()->status);
        $this->assertDatabaseMissing('enrolments', ['school_id' => $destination->id, 'learner_profile_id' => $learner->learner_profile_id]);
    }

    public function test_deactivation_withdraws_enrolment_and_blocks_managed_learner_access(): void
    {
        [$admin, $school] = $this->schoolContext();
        $user = User::factory()->create(['account_type' => 'managed_learner', 'learner_login_id' => 'NSL-ABC12345', 'learner_activated_at' => now(), 'password' => Hash::make('password')]);
        $profile = LearnerProfile::factory()->create(['user_id' => $user->id]);
        $learner = Enrolment::factory()->for($school)->for($profile)->create();

        $this->actingAs($admin)->post(route('schools.learners.deactivate', [$school, $learner]), ['deactivated_on' => '2026-08-01'])->assertRedirectToRoute('schools.learners.show', [$school, $learner]);

        $this->assertSame('withdrawn', $learner->fresh()->status);
        $this->assertNotNull($user->fresh()->learner_deactivated_at);
        $this->assertSame('inactive', $profile->fresh()->status);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'learner.deactivated']);
        Auth::logout();
        $this->post(route('learner.login'), ['learner_login_id' => $user->learner_login_id, 'password' => 'password'])->assertSessionHasErrors('learner_login_id');
    }

    /** @return array{0: User, 1: School} */
    private function schoolContext(): array
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        return [$admin, $school];
    }

    private function classGroup(School $school, string $name): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create();

        return ClassGroup::factory()->for($school)->for($academicYear)->create(['name' => $name]);
    }
}
