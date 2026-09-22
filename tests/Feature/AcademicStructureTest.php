<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_academic_year_and_terms(): void
    {
        [$admin, $school] = $this->schoolAdmin();

        $this->actingAs($admin)
            ->post(route('schools.academic-years.store', $school), [
                'name' => '2027',
                'starts_on' => '2027-01-01',
                'ends_on' => '2027-12-31',
            ])
            ->assertRedirectToRoute('schools.academic.index', $school);

        $academicYear = AcademicYear::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('schools.terms.store', [$school, $academicYear]), [
                'name' => 'Term 1',
                'starts_on' => '2027-01-01',
                'ends_on' => '2027-04-01',
            ])
            ->assertRedirectToRoute('schools.academic.index', $school);

        $this->assertDatabaseHas('terms', ['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'academic_year.created']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'term.created']);
    }

    public function test_active_staff_can_view_academic_structure_but_only_admin_can_create_it(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2028']);

        $this->actingAs($teacher)
            ->get(route('schools.academic.index', $school))
            ->assertOk()
            ->assertSee('2028');

        $this->actingAs($teacher)
            ->post(route('schools.academic-years.store', $school), [
                'name' => '2029',
                'starts_on' => '2029-01-01',
                'ends_on' => '2029-12-31',
            ])
            ->assertForbidden();
    }

    public function test_term_dates_cannot_overlap_within_an_academic_year(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2027',
            'starts_on' => '2027-01-01',
            'ends_on' => '2027-12-31',
        ]);
        Term::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'starts_on' => '2027-01-01',
            'ends_on' => '2027-04-01',
        ]);

        $this->actingAs($admin)
            ->post(route('schools.terms.store', [$school, $academicYear]), [
                'name' => 'Term 2',
                'starts_on' => '2027-03-15',
                'ends_on' => '2027-06-30',
            ])
            ->assertSessionHasErrors('starts_on');
    }

    public function test_invalid_academic_year_range_is_rejected(): void
    {
        [$admin, $school] = $this->schoolAdmin();

        $this->actingAs($admin)
            ->post(route('schools.academic-years.store', $school), [
                'name' => '2027',
                'starts_on' => '2027-12-31',
                'ends_on' => '2027-01-01',
            ])
            ->assertSessionHasErrors('ends_on');
    }

    public function test_member_cannot_view_another_schools_academic_structure(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $otherSchool = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $otherSchool->id, 'name' => 'Other Year']);

        $this->actingAs($admin)
            ->get(route('schools.academic.index', $otherSchool))
            ->assertNotFound()
            ->assertDontSee('Other Year');
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
}
