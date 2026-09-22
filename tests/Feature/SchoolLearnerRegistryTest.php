<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolLearnerRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_admit_and_view_a_learner(): void
    {
        [$user, $school] = $this->staffContext(SchoolRole::SchoolAdmin);

        $response = $this->actingAs($user)->post(route('schools.learners.store', $school), [
            'first_name' => 'Amani',
            'last_name' => 'Otieno',
            'preferred_name' => 'Ami',
            'date_of_birth' => '2012-05-14',
            'admission_number' => 'MWA-001',
        ]);

        $enrolment = Enrolment::query()->firstOrFail();

        $response->assertRedirectToRoute('schools.learners.index', $school);
        $this->assertDatabaseHas('learner_profiles', [
            'first_name' => 'Amani',
            'last_name' => 'Otieno',
            'preferred_name' => 'Ami',
        ]);
        $this->assertDatabaseHas('enrolments', [
            'school_id' => $school->id,
            'admission_number' => 'MWA-001',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'actor_user_id' => $user->id,
            'event_type' => 'learner.admitted',
            'auditable_id' => $enrolment->id,
        ]);

        $this->actingAs($user)
            ->get(route('schools.learners.show', [$school, $enrolment]))
            ->assertOk()
            ->assertSee('Ami Otieno')
            ->assertSee('MWA-001');
    }

    public function test_teacher_can_view_registry_but_cannot_admit_a_learner(): void
    {
        [$user, $school] = $this->staffContext(SchoolRole::Teacher);

        $this->actingAs($user)
            ->get(route('schools.learners.index', $school))
            ->assertOk()
            ->assertSee('Enrolled learners');

        $this->actingAs($user)
            ->post(route('schools.learners.store', $school), [
                'first_name' => 'Amani',
                'last_name' => 'Otieno',
                'admission_number' => 'MWA-002',
            ])
            ->assertForbidden();
    }

    public function test_duplicate_admission_number_is_scoped_to_one_school(): void
    {
        [$user, $school] = $this->staffContext(SchoolRole::SchoolAdmin);
        $otherSchool = School::factory()->create();
        $this->admit($user, $school, 'MWA-003');

        $this->actingAs($user)
            ->post(route('schools.learners.store', $school), [
                'first_name' => 'Second',
                'last_name' => 'Learner',
                'admission_number' => 'MWA-003',
            ])
            ->assertSessionHasErrors('admission_number');

        $otherMembership = $otherSchool->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $otherMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($user)
            ->post(route('schools.learners.store', $otherSchool), [
                'first_name' => 'Other',
                'last_name' => 'Learner',
                'admission_number' => 'MWA-003',
            ])
            ->assertRedirectToRoute('schools.learners.index', $otherSchool);
    }

    public function test_cross_school_learner_is_not_resolvable_through_nested_route(): void
    {
        [$user, $school] = $this->staffContext(SchoolRole::Teacher);
        $otherSchool = School::factory()->create();
        $otherEnrolment = $this->admit(User::factory()->create(), $otherSchool, 'OTH-001');

        $this->actingAs($user)
            ->get(route('schools.learners.show', [$school, $otherEnrolment]))
            ->assertNotFound();
    }

    public function test_non_staff_members_cannot_view_learner_registry(): void
    {
        [$user, $school] = $this->staffContext(SchoolRole::Guardian);

        $this->actingAs($user)
            ->get(route('schools.learners.index', $school))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: School}
     */
    private function staffContext(SchoolRole $role): array
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => $role]);

        return [$user, $school];
    }

    private function admit(User $actor, School $school, string $admissionNumber): Enrolment
    {
        $adminMembership = $school->memberships()->where('user_id', $actor->id)->first();
        if ($adminMembership === null) {
            $adminMembership = $school->memberships()->create([
                'user_id' => $actor->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $adminMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        }

        $this->actingAs($actor)->post(route('schools.learners.store', $school), [
            'first_name' => 'Existing',
            'last_name' => 'Learner',
            'admission_number' => $admissionNumber,
        ])->assertRedirect();

        return Enrolment::query()->where('school_id', $school->id)->where('admission_number', $admissionNumber)->firstOrFail();
    }
}
