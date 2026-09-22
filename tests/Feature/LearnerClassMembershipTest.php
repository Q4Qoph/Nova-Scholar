<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerClassMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_assign_a_learner_to_a_class_with_dates(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $learner = $this->enrolment($school);
        $classGroup = $this->classGroup($school, 'Grade 5 A');

        $response = $this->actingAs($admin)->post(route('schools.learners.class-memberships.store', [$school, $learner]), [
            'class_group_id' => $classGroup->id,
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $response->assertRedirectToRoute('schools.learners.show', [$school, $learner]);
        $membership = LearnerClassMembership::query()->where('enrolment_id', $learner->id)->firstOrFail();
        $this->assertSame($school->id, $membership->school_id);
        $this->assertSame($classGroup->id, $membership->class_group_id);
        $this->assertSame('2026-01-01', $membership->starts_on->toDateString());
        $this->assertSame('2026-04-30', $membership->ends_on?->toDateString());
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'learner_class_membership.created']);
    }

    public function test_historical_class_placements_can_be_created_without_overwriting_history(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $learner = $this->enrolment($school);
        $firstClass = $this->classGroup($school, 'Grade 5 A');
        $secondClass = $this->classGroup($school, 'Grade 5 B');

        $this->assign($admin, $school, $learner, $firstClass, '2026-01-01', '2026-04-30');
        $this->assign($admin, $school, $learner, $secondClass, '2026-05-01', null);

        $this->assertSame(2, LearnerClassMembership::query()->where('enrolment_id', $learner->id)->count());
        $this->actingAs($admin)
            ->get(route('schools.learners.show', [$school, $learner]))
            ->assertOk()
            ->assertSee('Grade 5 A')
            ->assertSee('Grade 5 B');
    }

    public function test_overlapping_class_placements_are_rejected(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $learner = $this->enrolment($school);
        $firstClass = $this->classGroup($school, 'Grade 5 A');
        $secondClass = $this->classGroup($school, 'Grade 5 B');

        $this->assign($admin, $school, $learner, $firstClass, '2026-01-01', null);

        $this->actingAs($admin)
            ->post(route('schools.learners.class-memberships.store', [$school, $learner]), [
                'class_group_id' => $secondClass->id,
                'starts_on' => '2026-05-01',
            ])
            ->assertSessionHasErrors('starts_on');

        $this->assertSame(1, LearnerClassMembership::query()->where('enrolment_id', $learner->id)->count());
    }

    public function test_active_staff_can_view_class_history_but_only_admin_can_assign(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);
        $learner = $this->enrolment($school);
        $classGroup = $this->classGroup($school, 'Grade 5 A');
        $this->assignAsAdmin($school, $learner, $classGroup);

        $this->actingAs($teacher)
            ->get(route('schools.learners.show', [$school, $learner]))
            ->assertOk()
            ->assertSee('Grade 5 A')
            ->assertDontSee('Assign to a class');

        $this->actingAs($teacher)
            ->post(route('schools.learners.class-memberships.store', [$school, $learner]), [
                'class_group_id' => $classGroup->id,
                'starts_on' => '2027-01-01',
            ])
            ->assertForbidden();
    }

    public function test_cross_school_class_cannot_be_assigned(): void
    {
        [$admin, $school] = $this->schoolContext(SchoolRole::SchoolAdmin);
        $learner = $this->enrolment($school);
        $otherSchool = School::factory()->create();
        $otherClass = $this->classGroup($otherSchool, 'Other school class');

        $this->actingAs($admin)
            ->post(route('schools.learners.class-memberships.store', [$school, $learner]), [
                'class_group_id' => $otherClass->id,
                'starts_on' => '2026-01-01',
            ])
            ->assertSessionHasErrors('class_group_id');
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

    private function enrolment(School $school): Enrolment
    {
        return Enrolment::factory()->for($school)->create();
    }

    private function classGroup(School $school, string $name): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create(['name' => '2026-'.$name]);

        return ClassGroup::factory()->for($school)->for($academicYear)->create(['name' => $name]);
    }

    private function assign(User $admin, School $school, Enrolment $learner, ClassGroup $classGroup, string $startsOn, ?string $endsOn): void
    {
        $payload = ['class_group_id' => $classGroup->id, 'starts_on' => $startsOn];
        if ($endsOn !== null) {
            $payload['ends_on'] = $endsOn;
        }

        $this->actingAs($admin)->post(route('schools.learners.class-memberships.store', [$school, $learner]), $payload)->assertRedirect();
    }

    private function assignAsAdmin(School $school, Enrolment $learner, ClassGroup $classGroup): void
    {
        $admin = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $this->assign($admin, $school, $learner, $classGroup, '2026-01-01', null);
    }
}
