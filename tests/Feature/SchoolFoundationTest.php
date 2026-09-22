<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\ProvisionSchool;
use App\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_platform_admin_can_provision_a_school_and_first_school_admin(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $administrator = User::factory()->create();

        $school = app(ProvisionSchool::class)->handle($actor, [
            'name' => 'Mwangaza Day School (Synthetic)',
            'slug' => 'mwangaza-day-synthetic',
            'school_type' => 'day',
        ], $administrator);

        $this->assertDatabaseHas('schools', ['id' => $school->id, 'slug' => 'mwangaza-day-synthetic']);
        $this->assertDatabaseHas('school_memberships', ['school_id' => $school->id, 'user_id' => $administrator->id, 'status' => 'active']);
        $this->assertDatabaseHas('school_role_assignments', ['role' => SchoolRole::SchoolAdmin->value]);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'actor_user_id' => $actor->id, 'event_type' => 'school.provisioned']);
        $this->assertSame(1, $administrator->schoolMemberships()->count());
        $this->assertInstanceOf(AuditEvent::class, $school->auditEvents()->first());
    }

    public function test_unverified_or_non_admin_users_cannot_provision_a_school(): void
    {
        $administrator = User::factory()->create();
        $actors = [
            User::factory()->create(['role' => UserRole::Student]),
            User::factory()->unverified()->create(['role' => UserRole::Admin]),
        ];

        foreach ($actors as $actor) {
            try {
                app(ProvisionSchool::class)->handle($actor, [
                    'name' => 'Blocked School',
                    'slug' => 'blocked-school-'.$actor->id,
                    'school_type' => 'day',
                ], $administrator);
                $this->fail('An unauthorized actor provisioned a school.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_unverified_first_school_administrator_is_rejected(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $administrator = User::factory()->unverified()->create();

        try {
            app(ProvisionSchool::class)->handle($actor, [
                'name' => 'Unverified School',
                'slug' => 'unverified-school',
                'school_type' => 'day',
            ], $administrator);
            $this->fail('An unverified administrator was provisioned.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_duplicate_school_slug_rolls_back_the_second_provisioning_attempt(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $firstAdministrator = User::factory()->create();
        $secondAdministrator = User::factory()->create();
        $service = app(ProvisionSchool::class);
        $schoolData = ['name' => 'Duplicate School', 'slug' => 'duplicate-school', 'school_type' => 'mixed'];
        $service->handle($actor, $schoolData, $firstAdministrator);

        try {
            $service->handle($actor, $schoolData, $secondAdministrator);
            $this->fail('Duplicate school slug should be rejected.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseCount('school_memberships', 1);
        $this->assertDatabaseCount('school_role_assignments', 1);
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_provisioning_cannot_assign_platform_admin_through_school_role_data(): void
    {
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        $administrator = User::factory()->create(['role' => UserRole::Student]);

        $school = app(ProvisionSchool::class)->handle($actor, [
            'name' => 'Scoped Roles School',
            'slug' => 'scoped-roles-school',
            'school_type' => 'boarding',
        ], $administrator);

        $this->assertSame(UserRole::Student, $administrator->fresh()->role);
        $this->assertSame(SchoolRole::SchoolAdmin, $school->memberships()->first()->roles()->first()->role);
    }
}
