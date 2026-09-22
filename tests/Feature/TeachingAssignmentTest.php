<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_assign_an_active_teacher_to_a_class_and_subject(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        [$teacher] = $this->teacher($school);
        [$academicYear, $classGroup, $subject] = $this->academicRecords($school);

        $this->actingAs($admin)
            ->post(route('schools.teaching-assignments.store', $school), [
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'teacher_user_id' => $teacher->id,
            ])
            ->assertRedirectToRoute('schools.academic.index', $school);

        $this->assertDatabaseHas('teaching_assignments', [
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'teaching_assignment.created',
        ]);
        $this->assertSame($school->id, $academicYear->school_id);
    }

    public function test_active_staff_can_view_assignments_but_only_admin_can_create_them(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        [$teacher] = $this->teacher($school);
        [, $classGroup, $subject] = $this->academicRecords($school);
        $this->assign($admin, $school, $classGroup, $subject, $teacher);
        $this->actingAs($teacher)
            ->get(route('schools.academic.index', $school))
            ->assertOk()
            ->assertSee($teacher->name)
            ->assertSee($subject->name);

        $this->actingAs($teacher)
            ->post(route('schools.teaching-assignments.store', $school), [
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'teacher_user_id' => $teacher->id,
            ])
            ->assertForbidden();
    }

    public function test_non_teacher_and_duplicate_assignments_are_rejected(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $bursar = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $bursar->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Bursar]);
        [$teacher] = $this->teacher($school);
        [, $classGroup, $subject] = $this->academicRecords($school);
        $this->assign($admin, $school, $classGroup, $subject, $teacher);

        $this->actingAs($admin)
            ->post(route('schools.teaching-assignments.store', $school), [
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'teacher_user_id' => $bursar->id,
            ])
            ->assertSessionHasErrors('teacher_user_id');

        $this->actingAs($admin)
            ->post(route('schools.teaching-assignments.store', $school), [
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'teacher_user_id' => $teacher->id,
            ])
            ->assertSessionHasErrors('class_group_id');
    }

    public function test_cross_school_class_and_subject_cannot_be_assigned(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        [$teacher] = $this->teacher($school);
        $otherSchool = School::factory()->create();
        [, $otherClassGroup, $otherSubject] = $this->academicRecords($otherSchool);

        $this->actingAs($admin)
            ->post(route('schools.teaching-assignments.store', $school), [
                'class_group_id' => $otherClassGroup->id,
                'subject_id' => $otherSubject->id,
                'teacher_user_id' => $teacher->id,
            ])
            ->assertSessionHasErrors(['class_group_id', 'subject_id']);
    }

    public function test_member_cannot_view_another_school_assignments(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $otherSchool = School::factory()->create();
        TeachingAssignment::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($admin)
            ->get(route('schools.academic.index', $otherSchool))
            ->assertNotFound();
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

    /**
     * @return array{0: User}
     */
    private function teacher(School $school): array
    {
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        return [$teacher];
    }

    /**
     * @return array{0: AcademicYear, 1: ClassGroup, 2: Subject}
     */
    private function academicRecords(School $school): array
    {
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'name' => 'Grade 5 A']);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'code' => 'MATH']);

        return [$academicYear, $classGroup, $subject];
    }

    private function assign(User $admin, School $school, ClassGroup $classGroup, Subject $subject, User $teacher): TeachingAssignment
    {
        $this->actingAs($admin)->post(route('schools.teaching-assignments.store', $school), [
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
        ])->assertRedirect();

        return TeachingAssignment::query()->latest('id')->firstOrFail();
    }
}
