<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_verify_guardian_and_guardian_can_see_linked_learner(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create(['email' => 'guardian@example.test']);
        $enrolment = $this->admit($admin, $school, 'G-001');

        $this->actingAs($admin)
            ->post(route('schools.learners.guardians.store', [$school, $enrolment]), [
                'email' => $guardian->email,
                'relationship' => 'Parent',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Guardian relationship verified.');

        $this->assertDatabaseHas('guardian_links', [
            'school_id' => $school->id,
            'enrolment_id' => $enrolment->id,
            'guardian_user_id' => $guardian->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'guardian.linked',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('Existing Learner')
            ->assertSee('Parent')
            ->assertSee('Guardian portal');
    }

    public function test_guardian_can_have_multiple_linked_learners_but_not_unrelated_learners(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $firstLearner = $this->admit($admin, $school, 'G-002');
        $secondLearner = $this->admit($admin, $school, 'G-003');
        $unlinkedLearner = $this->admit($admin, $school, 'G-004');

        foreach ([$firstLearner, $secondLearner] as $learner) {
            $this->linkGuardian($admin, $school, $learner, $guardian);
        }

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('G-002')
            ->assertSee('G-003')
            ->assertDontSee('G-004');

        $this->assertDatabaseMissing('guardian_links', ['enrolment_id' => $unlinkedLearner->id, 'guardian_user_id' => $guardian->id]);
    }

    public function test_revoked_guardian_relationship_is_removed_from_guardian_portal(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $enrolment = $this->admit($admin, $school, 'G-005');
        $this->linkGuardian($admin, $school, $enrolment, $guardian);
        $link = GuardianLink::query()->where('enrolment_id', $enrolment->id)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('schools.learners.guardians.destroy', [$school, $enrolment, $link]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Guardian relationship revoked.');

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('No active learner relationships are available.')
            ->assertDontSee('G-005');
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'guardian.link.revoked']);
    }

    public function test_unverified_guardian_and_non_admin_cannot_create_a_link(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $unverifiedGuardian = User::factory()->unverified()->create();
        $teacher = User::factory()->create();
        $teacherMembership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $teacherMembership->roles()->create(['role' => SchoolRole::Teacher]);
        $enrolment = $this->admit($admin, $school, 'G-006');

        $this->actingAs($admin)
            ->post(route('schools.learners.guardians.store', [$school, $enrolment]), [
                'email' => $unverifiedGuardian->email,
                'relationship' => 'Guardian',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($teacher)
            ->post(route('schools.learners.guardians.store', [$school, $enrolment]), [
                'email' => $admin->email,
                'relationship' => 'Guardian',
            ])
            ->assertForbidden();
    }

    public function test_cross_school_learner_cannot_be_used_for_a_guardian_link(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $otherSchool = School::factory()->create();
        $otherAdmin = User::factory()->create();
        $membership = $otherSchool->memberships()->create(['user_id' => $otherAdmin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $otherLearner = $this->admit($otherAdmin, $otherSchool, 'OTHER-001');
        $guardian = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('schools.learners.guardians.store', [$school, $otherLearner]), [
                'email' => $guardian->email,
                'relationship' => 'Guardian',
            ])
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: School}
     */
    private function schoolAdmin(): array
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        return [$admin, $school];
    }

    private function admit(User $admin, School $school, string $admissionNumber): Enrolment
    {
        $this->actingAs($admin)->post(route('schools.learners.store', $school), [
            'first_name' => 'Existing',
            'last_name' => 'Learner',
            'admission_number' => $admissionNumber,
        ])->assertRedirect();

        return Enrolment::query()->where('school_id', $school->id)->where('admission_number', $admissionNumber)->firstOrFail();
    }

    private function linkGuardian(User $admin, School $school, Enrolment $enrolment, User $guardian): GuardianLink
    {
        $this->actingAs($admin)->post(route('schools.learners.guardians.store', [$school, $enrolment]), [
            'email' => $guardian->email,
            'relationship' => 'Guardian',
        ])->assertRedirect();

        return GuardianLink::query()->where('enrolment_id', $enrolment->id)->where('guardian_user_id', $guardian->id)->firstOrFail();
    }
}
