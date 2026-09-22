<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SchoolSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_navigation_lists_active_school_memberships(): void
    {
        $user = User::factory()->create();
        $activeSchool = School::factory()->create(['name' => 'Active School']);
        $secondSchool = School::factory()->create(['name' => 'Second School']);
        $inactiveSchool = School::factory()->create(['name' => 'Suspended School', 'status' => 'suspended']);

        foreach ([$activeSchool, $secondSchool, $inactiveSchool] as $school) {
            $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
            $membership->roles()->create(['role' => SchoolRole::Teacher]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('filament.school.pages.home', ['tenant' => $activeSchool->slug]));
    }

    public function test_removed_membership_is_not_listed_in_authenticated_navigation(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['name' => 'Former School']);

        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'removed',
            'joined_at' => now()->subDay(),
            'removed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Former School')
            ->assertDontSee(route('filament.school.pages.home', ['tenant' => $school->slug]), false);
    }

    public function test_school_view_policy_requires_active_membership(): void
    {
        $user = User::factory()->create();
        $memberSchool = School::factory()->create();
        $otherSchool = School::factory()->create();
        $suspendedSchool = School::factory()->create(['status' => 'suspended']);

        $memberSchool->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $memberSchool));
        $this->assertFalse(Gate::forUser($user)->allows('view', $otherSchool));
        $this->assertFalse(Gate::forUser($user)->allows('view', $suspendedSchool));
    }

    public function test_overview_policy_rechecks_membership_before_loading_school_data(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['name' => 'Policy Protected School']);
        $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $school->memberships()->update(['status' => 'removed', 'removed_at' => now()]);

        $this->actingAs($user)
            ->get(route('schools.overview', $school))
            ->assertNotFound();
    }
}
