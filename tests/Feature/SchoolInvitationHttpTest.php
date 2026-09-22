<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\ProvisionSchool;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolInvitationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_invitee_can_review_and_accept_invitation(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $invitee = User::factory()->create(['email' => 'http-teacher@example.test']);

        $response = $this->actingAs($admin)->post(route('schools.invitations.store', $school), [
            'email' => $invitee->email,
            'role' => SchoolRole::Teacher->value,
        ]);

        $response->assertRedirectToRoute('schools.overview', $school)->assertSessionHas('invitation_url');
        $invitationUrl = $response->getSession()->get('invitation_url');

        $this->actingAs($invitee)->get($invitationUrl)->assertOk()->assertSee($school->name);
        $this->actingAs($invitee)->post($invitationUrl)->assertRedirectToRoute('schools.overview', $school);
        $this->assertDatabaseHas('school_memberships', ['school_id' => $school->id, 'user_id' => $invitee->id, 'status' => 'active']);
    }

    public function test_non_admin_cannot_create_invitation_and_invalid_payload_is_rejected(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher)->post(route('schools.invitations.store', $school), [
            'email' => 'missing@example.test',
            'role' => 'learner',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('schools.invitations.store', $school), [
            'email' => 'missing@example.test',
            'role' => 'learner',
        ])->assertSessionHasErrors(['email', 'role']);
    }

    public function test_only_intended_user_can_view_invitation_and_admin_can_revoke_it(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $invitee = User::factory()->create();
        $otherUser = User::factory()->create();
        $response = $this->actingAs($admin)->post(route('schools.invitations.store', $school), [
            'email' => $invitee->email,
            'role' => SchoolRole::Bursar->value,
        ]);
        $invitationUrl = $response->getSession()->get('invitation_url');
        $invitation = $school->invitations()->firstOrFail();

        $this->actingAs($otherUser)->get($invitationUrl)->assertNotFound();
        $this->actingAs($admin)->delete(route('schools.invitations.destroy', [$school, $invitation]))->assertRedirect();
        $this->assertDatabaseHas('school_invitations', ['id' => $invitation->id]);
        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->actingAs($invitee)->get($invitationUrl)->assertNotFound();
    }

    public function test_admin_can_assign_remove_role_and_remove_membership(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($admin)->post(route('schools.memberships.roles.store', [$school, $membership]), ['role' => SchoolRole::Bursar->value])->assertRedirect();
        $this->assertDatabaseHas('school_role_assignments', ['school_membership_id' => $membership->id, 'role' => SchoolRole::Bursar->value]);
        $this->actingAs($admin)->delete(route('schools.memberships.roles.destroy', [$school, $membership, SchoolRole::Bursar->value]))->assertRedirect();
        $this->actingAs($admin)->delete(route('schools.memberships.destroy', [$school, $membership]))->assertRedirect();
        $this->assertDatabaseHas('school_memberships', ['id' => $membership->id, 'status' => 'removed']);
    }

    public function test_nested_school_routes_cannot_mutate_a_membership_from_another_school(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $otherSchool = School::factory()->create();
        $otherMembership = $otherSchool->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $otherMembership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($admin)
            ->post(route('schools.memberships.roles.store', [$school, $otherMembership]), ['role' => SchoolRole::Bursar->value])
            ->assertNotFound();
        $this->assertDatabaseMissing('school_role_assignments', ['school_membership_id' => $otherMembership->id, 'role' => SchoolRole::Bursar->value]);
    }

    private function provisionSchool(): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $school = app(ProvisionSchool::class)->handle($admin, [
            'name' => 'HTTP Invitation School',
            'slug' => 'http-invitation-school-'.fake()->unique()->numberBetween(1, 999999),
            'school_type' => 'day',
        ], $admin);

        return [$school, $admin];
    }
}
