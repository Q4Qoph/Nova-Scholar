<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_access_the_platform_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->get('/platform')
            ->assertOk();
    }

    public function test_non_platform_admin_cannot_access_the_platform_panel(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get('/platform')
            ->assertForbidden();
    }

    public function test_school_staff_can_access_only_their_active_school_panel(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'mwangaza-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->followingRedirects()
            ->actingAs($teacher)
            ->get('/school/mwangaza-school')
            ->assertOk()
            ->assertSee('School overview')
            ->assertSee('Open current overview');
    }

    public function test_school_tenant_root_renders_the_school_dashboard_without_a_redirect_gap(): void
    {
        $schoolAdmin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'dashboard-school']);
        $membership = $school->memberships()->create([
            'user_id' => $schoolAdmin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($schoolAdmin)
            ->get('/school/dashboard-school')
            ->assertOk()
            ->assertSee('School overview')
            ->assertSee('Open current overview');
    }

    public function test_guardian_cannot_access_the_school_staff_panel(): void
    {
        $guardian = User::factory()->create();
        $school = School::factory()->create(['slug' => 'guardian-school']);
        $membership = $school->memberships()->create([
            'user_id' => $guardian->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Guardian]);

        $this->actingAs($guardian)
            ->get('/school/guardian-school')
            ->assertForbidden();
    }

    public function test_removed_school_staff_membership_cannot_access_the_school_panel(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'removed-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'removed',
            'joined_at' => now()->subDay(),
            'removed_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher)
            ->get('/school/removed-school')
            ->assertForbidden();
    }
}
