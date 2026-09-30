<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Policies\SchoolCoursePolicy;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolCoursePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_can_view_course_but_another_teacher_and_revoked_member_cannot(): void
    {
        [$school, $teacher, $course] = $this->courseContext();
        $policy = new SchoolCoursePolicy;
        $otherTeacher = User::factory()->create();
        $otherMembership = $school->memberships()->create([
            'user_id' => $otherTeacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $otherMembership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->assertTrue($policy->view($teacher, $course));
        $this->assertFalse($policy->view($otherTeacher, $course));

        $teacher->schoolMemberships()->where('school_id', $school->id)->update([
            'status' => 'removed',
            'removed_at' => now(),
        ]);

        $this->assertFalse($policy->view($teacher, $course));
    }

    public function test_school_admin_can_manage_a_course_for_another_teacher_assignment(): void
    {
        [$school, , $course] = $this->courseContext();
        $admin = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->assertTrue((new SchoolCoursePolicy)->update($admin, $course));
    }

    public function test_course_is_denied_when_its_school_does_not_match_its_assignment(): void
    {
        [$school, $teacher, $course] = $this->courseContext();
        $otherSchool = School::factory()->create();
        $course->forceFill(['school_id' => $otherSchool->id])->save();

        $this->assertNotSame($school->id, $course->school_id);
        $this->assertFalse((new SchoolCoursePolicy)->view($teacher, $course->fresh()));
    }

    /** @return array{0: School, 1: User, 2: SchoolCourse} */
    private function courseContext(): array
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->for($school)->create([
            'starts_on' => today()->subMonth(),
            'ends_on' => today()->addMonths(10),
        ]);
        $classGroup = ClassGroup::factory()->for($school)->for($academicYear)->create();
        $subject = Subject::factory()->for($school)->create();
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'status' => 'active',
        ]);
        $course = SchoolCourse::factory()->create([
            'school_id' => $school->id,
            'teaching_assignment_id' => $assignment->id,
            'created_by_user_id' => $teacher->id,
        ]);

        return [$school, $teacher, $course];
    }
}
