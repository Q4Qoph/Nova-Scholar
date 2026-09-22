<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_active_member_can_view_their_school_overview(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['name' => 'Mwangaza Day School']);
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($user)
            ->get(route('schools.overview', $school))
            ->assertOk()
            ->assertSee('Mwangaza Day School')
            ->assertSee('Teacher');
    }

    public function test_member_cannot_view_another_school_overview(): void
    {
        $user = User::factory()->create();
        $memberSchool = School::factory()->create();
        $otherSchool = School::factory()->create(['name' => 'Other Synthetic School']);
        $memberSchool->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('schools.overview', $otherSchool))
            ->assertNotFound()
            ->assertDontSee('Other Synthetic School');
    }

    public function test_removed_membership_cannot_view_school_overview(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'removed',
            'joined_at' => now()->subDay(),
            'removed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('schools.overview', $school))
            ->assertNotFound();
    }

    public function test_inactive_school_cannot_be_selected_as_context(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['status' => 'suspended']);
        $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('schools.overview', $school))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_before_school_context_check(): void
    {
        $school = School::factory()->create();

        $this->get(route('schools.overview', $school))
            ->assertRedirectToRoute('login');
    }

    public function test_unverified_member_is_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $school = School::factory()->create();
        $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('schools.overview', $school))
            ->assertRedirectToRoute('verification.notice');
    }
}
