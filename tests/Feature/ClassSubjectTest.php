<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_class_group_and_subject(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);

        $this->actingAs($admin)
            ->post(route('schools.class-groups.store', [$school, $academicYear]), [
                'name' => 'Grade 5 A',
                'grade_level' => 'Grade 5',
                'stream' => 'A',
            ])
            ->assertRedirectToRoute('schools.academic.index', $school);

        $this->actingAs($admin)
            ->post(route('schools.subjects.store', $school), [
                'name' => 'Mathematics',
                'code' => 'MATH',
            ])
            ->assertRedirectToRoute('schools.academic.index', $school);

        $this->assertDatabaseHas('class_groups', ['academic_year_id' => $academicYear->id, 'name' => 'Grade 5 A']);
        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'code' => 'MATH']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'class_group.created']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'subject.created']);
    }

    public function test_active_staff_can_view_classes_and_subjects_but_cannot_create_them(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2028']);
        ClassGroup::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Grade 6 A']);
        Subject::factory()->create(['school_id' => $school->id, 'code' => 'ENG']);

        $this->actingAs($teacher)
            ->get(route('schools.academic.index', $school))
            ->assertOk()
            ->assertSee('Grade 6 A')
            ->assertSee('ENG');

        $this->actingAs($teacher)
            ->post(route('schools.subjects.store', $school), ['name' => 'Science', 'code' => 'SCI'])
            ->assertForbidden();
    }

    public function test_class_names_are_unique_within_an_academic_year_and_subject_codes_within_a_school(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2029']);
        ClassGroup::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Grade 7 A']);
        Subject::factory()->create(['school_id' => $school->id, 'code' => 'KISW']);

        $this->actingAs($admin)
            ->post(route('schools.class-groups.store', [$school, $academicYear]), [
                'name' => 'Grade 7 A',
                'grade_level' => 'Grade 7',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('schools.subjects.store', $school), ['name' => 'Kiswahili', 'code' => 'KISW'])
            ->assertSessionHasErrors('code');
    }

    public function test_same_class_name_and_subject_code_are_allowed_in_another_school(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $otherSchool = School::factory()->create();
        $otherMembership = $otherSchool->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $otherMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $otherSchool->id, 'name' => '2027']);

        $this->actingAs($admin)
            ->post(route('schools.class-groups.store', [$otherSchool, $academicYear]), [
                'name' => 'Grade 5 A',
                'grade_level' => 'Grade 5',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('schools.subjects.store', $otherSchool), ['name' => 'Mathematics', 'code' => 'MATH'])
            ->assertRedirect();
    }

    public function test_member_cannot_view_another_school_academic_records(): void
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
