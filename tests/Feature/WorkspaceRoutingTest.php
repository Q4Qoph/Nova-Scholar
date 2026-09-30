<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_learner_enters_learner_home_and_inactive_account_cannot_fall_back(): void
    {
        $user = User::factory()->create(['account_type' => 'managed_learner', 'learner_activated_at' => now()]);
        $this->actingAs($user)->get(route('workspace'))->assertRedirect(route('learner.dashboard'));
        $user->update(['learner_deactivated_at' => now()]);
        $this->get(route('workspace'))->assertForbidden();
        $this->get(route('study'))->assertForbidden();
    }

    public function test_verified_link_routes_guardian_to_children_and_revocation_removes_destination(): void
    {
        $user = User::factory()->create();
        $link = GuardianLink::factory()->create(['guardian_user_id' => $user->id, 'enrolment_id' => Enrolment::factory()->create(['school_id' => $familySchool = School::factory()->create()])->id, 'school_id' => $familySchool->id]);
        $this->actingAs($user)->get(route('workspace'))->assertRedirect(route('guardian.learners.index'));
        $link->update(['revoked_at' => now()]);
        $this->get(route('workspace'))->assertRedirect(route('study'));
    }

    public function test_multiple_schools_and_family_access_offer_only_authorized_destinations(): void
    {
        $user = User::factory()->create();
        foreach (['First school', 'Second school', 'Suspended school'] as $name) {
            $school = School::factory()->create(['name' => $name, 'status' => $name === 'Suspended school' ? 'suspended' : 'active']);
            $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
            $membership->roles()->create(['role' => SchoolRole::Teacher]);
        }
        GuardianLink::factory()->create(['guardian_user_id' => $user->id, 'enrolment_id' => Enrolment::factory()->create(['school_id' => $familySchool = School::factory()->create()])->id, 'school_id' => $familySchool->id]);
        $this->actingAs($user)->get(route('workspace'))->assertOk()->assertSee('First school')->assertSee('Second school')
            ->assertSee('My children')->assertDontSee('Suspended school');
        $this->get(route('study'))->assertOk()->assertSee('Your learning space');
    }

    public function test_unverified_adult_and_authenticated_guest_route_use_workspace_dispatch(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('workspace'))->assertRedirect(route('verification.notice'));
        $this->get(route('login'))->assertRedirect(route('workspace'));
    }
}
