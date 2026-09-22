<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\AcceptSchoolInvitation;
use App\Services\Schools\CreateSchoolInvitation;
use App\Services\Schools\ManageSchoolRole;
use App\Services\Schools\ProvisionSchool;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_verified_invitee_can_accept_a_one_use_invitation(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $invitee = User::factory()->create(['email' => 'teacher@example.test']);

        $created = app(CreateSchoolInvitation::class)->handle($admin, $school, $invitee->email, SchoolRole::Teacher);
        $invitation = $created['invitation'];
        $membership = app(AcceptSchoolInvitation::class)->handle($invitee, $created['token']);

        $this->assertNotSame($created['token'], $invitation->token_hash);
        $this->assertDatabaseHas('school_invitations', ['id' => $invitation->id, 'accepted_at' => $invitation->fresh()->accepted_at]);
        $this->assertDatabaseHas('school_memberships', ['id' => $membership->id, 'school_id' => $school->id, 'user_id' => $invitee->id, 'status' => 'active']);
        $this->assertDatabaseHas('school_role_assignments', ['school_membership_id' => $membership->id, 'role' => SchoolRole::Teacher->value]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'school.invitation.created']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'school.invitation.accepted']);

        try {
            app(AcceptSchoolInvitation::class)->handle($invitee, $created['token']);
            $this->fail('An invitation was accepted twice.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_non_admin_or_cross_school_member_cannot_create_an_invitation(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $teacher = User::factory()->create();
        $otherSchool = School::factory()->create();
        $otherSchool->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $invitee = User::factory()->create();

        foreach ([[$teacher, $school], [$admin, $otherSchool]] as [$actor, $targetSchool]) {
            try {
                app(CreateSchoolInvitation::class)->handle($actor, $targetSchool, $invitee->email, SchoolRole::Teacher);
                $this->fail('An unauthorized invitation was created.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('school_invitations', 0);
    }

    public function test_unverified_or_already_member_users_cannot_be_invited(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $unverified = User::factory()->unverified()->create();
        $existing = User::factory()->create();
        $school->memberships()->create(['user_id' => $existing->id, 'status' => 'active', 'joined_at' => now()]);

        foreach ([$unverified, $existing] as $invitee) {
            try {
                app(CreateSchoolInvitation::class)->handle($admin, $school, $invitee->email, SchoolRole::Teacher);
                $this->fail('An invalid invitee was invited.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('school_invitations', 0);
    }

    public function test_expired_revoked_and_wrong_user_tokens_are_rejected(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $invitee = User::factory()->create();
        $wrongUser = User::factory()->create();
        $service = app(CreateSchoolInvitation::class);

        $expired = $service->handle($admin, $school, $invitee->email, SchoolRole::Teacher);
        $expired['invitation']->update(['expires_at' => now()->subMinute()]);
        $this->assertInvitationRejected($invitee, $expired['token']);

        $revoked = $service->handle($admin, $school, $invitee->email, SchoolRole::Bursar);
        $service->revoke($admin, $revoked['invitation']);
        $this->assertInvitationRejected($invitee, $revoked['token']);

        $wrongUserInvitation = $service->handle($admin, $school, $invitee->email, SchoolRole::Teacher);
        $this->assertInvitationRejected($wrongUser, $wrongUserInvitation['token']);
    }

    public function test_school_admin_can_assign_and_remove_staff_roles_but_not_the_last_admin(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $secondAdmin = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $secondAdmin->id, 'status' => 'active', 'joined_at' => now()]);
        $service = app(ManageSchoolRole::class);
        $service->assign($admin, $membership, SchoolRole::Teacher);
        $service->assign($admin, $membership, SchoolRole::SchoolAdmin);
        $service->remove($admin, $membership, SchoolRole::Teacher);

        $this->assertDatabaseMissing('school_role_assignments', ['school_membership_id' => $membership->id, 'role' => SchoolRole::Teacher->value]);
        $this->assertDatabaseHas('school_role_assignments', ['school_membership_id' => $membership->id, 'role' => SchoolRole::SchoolAdmin->value]);

        $service->remove($admin, $membership, SchoolRole::SchoolAdmin);
        try {
            $service->remove($admin, $school->memberships()->where('user_id', $admin->id)->firstOrFail(), SchoolRole::SchoolAdmin);
            $this->fail('The last school administrator was removed.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_non_admin_cannot_assign_roles_or_remove_memberships(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $service = app(ManageSchoolRole::class);

        try {
            $service->assign($teacher, $membership, SchoolRole::Bursar);
            $this->fail('A non-admin assigned a school role.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $service->removeMembership($admin, $membership);
        $this->assertDatabaseHas('school_memberships', ['id' => $membership->id, 'status' => 'removed']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'school.membership.removed']);
    }

    public function test_school_roles_never_change_platform_user_role(): void
    {
        [$school, $admin] = $this->provisionSchool();
        $teacher = User::factory()->create(['role' => UserRole::Student]);
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);

        app(ManageSchoolRole::class)->assign($admin, $membership, SchoolRole::SchoolAdmin);

        $this->assertSame(UserRole::Student, $teacher->fresh()->role);
    }

    private function provisionSchool(): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $school = app(ProvisionSchool::class)->handle($admin, [
            'name' => 'Synthetic Invitation School',
            'slug' => 'synthetic-invitation-school-'.fake()->unique()->numberBetween(1, 999999),
            'school_type' => 'day',
        ], $admin);

        return [$school, $admin];
    }

    private function assertInvitationRejected(User $user, string $token): void
    {
        try {
            app(AcceptSchoolInvitation::class)->handle($user, $token);
            $this->fail('An invalid invitation was accepted.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }
}
