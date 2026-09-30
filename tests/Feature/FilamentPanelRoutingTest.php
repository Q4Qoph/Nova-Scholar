<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_dashboard_always_redirects_to_platform_panel(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertRedirect(route('filament.platform.home'));
    }

    public function test_eligible_school_staff_dashboard_always_redirects_to_first_authorized_school_panel(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'routed-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertRedirect(route('filament.school.pages.home', ['tenant' => $school->slug]));
    }

    public function test_accounts_without_school_staff_membership_keep_the_personal_dashboard(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)
            ->get(route('study'))
            ->assertOk()
            ->assertSee('Your learning space');
    }

    public function test_landing_page_and_panel_login_routes_always_offer_filament_entry(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/school/login', false)
            ->assertSee('/platform/login', false);

        $this->get('/school/login')->assertOk();
        $this->get('/platform/login')->assertOk();
    }
}
