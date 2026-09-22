<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingWorkspaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_exposes_the_distinct_workspace_entry_points(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/school/login', false)
            ->assertSee('/platform/login', false)
            ->assertSee('/learner/login', false)
            ->assertSee(route('login'), false)
            ->assertSee('School workspace')
            ->assertSee('Platform workspace')
            ->assertSee('Learner sign in')
            ->assertSee('Personal workspace');
    }

    public function test_authenticated_dashboard_points_school_members_to_filament_workspace(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['slug' => 'landing-school']);
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('filament.school.pages.home', ['tenant' => $school->slug]));
    }

    public function test_platform_admin_dashboard_points_to_the_platform_workspace(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('filament.platform.home'));
    }
}
