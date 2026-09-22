<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceEntry;
use App\Models\AttendanceSession;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_can_save_a_register_and_unmarked_learners_remain_unmarked(): void
    {
        [$teacher, $school, $assignment, $classGroup] = $this->attendanceContext();
        $presentLearner = $this->learnerInClass($school, $classGroup, 'A-001');
        $unmarkedLearner = $this->learnerInClass($school, $classGroup, 'A-002');

        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), [
            'class_group_id' => $classGroup->id,
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2026-09-22',
            'entries' => [['enrolment_id' => $presentLearner->id, 'status' => 'present']],
        ])->assertRedirect();

        $session = AttendanceSession::query()->firstOrFail();
        $this->assertSame(1, $session->version);
        $this->assertDatabaseHas('attendance_entries', ['attendance_session_id' => $session->id, 'enrolment_id' => $presentLearner->id, 'status' => 'present']);
        $this->assertDatabaseHas('attendance_entries', ['attendance_session_id' => $session->id, 'enrolment_id' => $unmarkedLearner->id, 'status' => 'unmarked']);

        $this->actingAs($teacher)->get(route('schools.attendance.index', ['school' => $school, 'session' => $session->id]))
            ->assertOk()->assertSee($presentLearner->learnerProfile->first_name)->assertSee('Unmarked');
    }

    public function test_register_retry_updates_once_and_stale_version_does_not_overwrite_entries(): void
    {
        [$teacher, $school, $assignment, $classGroup] = $this->attendanceContext();
        $learner = $this->learnerInClass($school, $classGroup, 'A-003');
        $payload = ['class_group_id' => $classGroup->id, 'teaching_assignment_id' => $assignment->id, 'session_date' => '2026-09-22', 'entries' => [['enrolment_id' => $learner->id, 'status' => 'present']]];

        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), $payload)->assertRedirect();
        $session = AttendanceSession::query()->firstOrFail();
        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), array_merge($payload, ['attendance_session_id' => $session->id, 'version' => 1, 'entries' => [['enrolment_id' => $learner->id, 'status' => 'late', 'correction_reason' => 'Teacher confirmed the learner arrived late.']]]))->assertRedirect();

        $this->assertSame(2, $session->fresh()->version);
        $this->assertSame(1, AttendanceEntry::query()->where('attendance_session_id', $session->id)->count());
        $this->assertDatabaseHas('attendance_entries', ['attendance_session_id' => $session->id, 'status' => 'late']);
        $this->assertDatabaseHas('attendance_entry_corrections', ['attendance_session_id' => $session->id, 'from_status' => 'present', 'to_status' => 'late', 'reason' => 'Teacher confirmed the learner arrived late.', 'session_version' => 2]);
        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), array_merge($payload, [
            'attendance_session_id' => $session->id,
            'version' => 1,
            'entries' => [['enrolment_id' => $learner->id, 'status' => 'absent', 'correction_reason' => 'Stale correction.']],
        ]))->assertSessionHasErrors('version');
        $this->assertDatabaseHas('attendance_entries', ['attendance_session_id' => $session->id, 'status' => 'late']);
    }

    public function test_status_change_requires_a_reason_and_preserves_the_existing_entry(): void
    {
        [$teacher, $school, $assignment, $classGroup] = $this->attendanceContext();
        $learner = $this->learnerInClass($school, $classGroup, 'A-004');
        $payload = ['class_group_id' => $classGroup->id, 'teaching_assignment_id' => $assignment->id, 'session_date' => '2026-09-22', 'entries' => [['enrolment_id' => $learner->id, 'status' => 'present']]];

        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), $payload)->assertRedirect();
        $session = AttendanceSession::query()->firstOrFail();
        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), array_merge($payload, ['attendance_session_id' => $session->id, 'version' => 1, 'entries' => [['enrolment_id' => $learner->id, 'status' => 'absent']]]))->assertSessionHasErrors();

        $this->assertDatabaseHas('attendance_entries', ['attendance_session_id' => $session->id, 'enrolment_id' => $learner->id, 'status' => 'present']);
        $this->assertDatabaseCount('attendance_entry_corrections', 0);
    }

    public function test_teacher_cannot_save_another_teachers_assignment(): void
    {
        [$teacher, $school, $assignment, $classGroup] = $this->attendanceContext();
        $otherTeacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $otherTeacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($otherTeacher)->post(route('schools.attendance.store', $school), ['class_group_id' => $classGroup->id, 'teaching_assignment_id' => $assignment->id, 'session_date' => '2026-09-22'])->assertSessionHasErrors('teaching_assignment_id');
        $this->assertDatabaseCount('attendance_sessions', 0);
        $this->assertNotSame($teacher->id, $otherTeacher->id);
    }

    public function test_school_attendance_is_not_visible_across_schools_and_unknown_learners_are_rejected(): void
    {
        [$teacher, $school, $assignment, $classGroup] = $this->attendanceContext();
        $otherSchool = School::factory()->create();
        $otherClass = $this->classGroup($otherSchool);
        $otherSubject = Subject::factory()->for($otherSchool)->create();
        TeachingAssignment::factory()->create(['school_id' => $otherSchool->id, 'class_group_id' => $otherClass->id, 'subject_id' => $otherSubject->id, 'teacher_user_id' => $teacher->id]);
        $foreignLearner = $this->learnerInClass($otherSchool, $otherClass, 'B-001');

        $this->actingAs($teacher)->get(route('schools.attendance.index', ['school' => $otherSchool]))->assertNotFound();
        $this->actingAs($teacher)->post(route('schools.attendance.store', $school), ['class_group_id' => $classGroup->id, 'teaching_assignment_id' => $assignment->id, 'session_date' => '2026-09-22', 'entries' => [['enrolment_id' => $foreignLearner->id, 'status' => 'present']]])->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    /** @return array{0: User, 1: School, 2: TeachingAssignment, 3: ClassGroup} */
    private function attendanceContext(): array
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $classGroup = $this->classGroup($school);
        $subject = Subject::factory()->for($school)->create();
        $assignment = TeachingAssignment::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id, 'subject_id' => $subject->id, 'teacher_user_id' => $teacher->id]);

        return [$teacher, $school, $assignment, $classGroup];
    }

    private function classGroup(School $school): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);

        return ClassGroup::factory()->for($school)->for($academicYear)->create();
    }

    private function learnerInClass(School $school, ClassGroup $classGroup, string $admissionNumber): Enrolment
    {
        $enrolment = Enrolment::factory()->for($school)->create(['admission_number' => $admissionNumber]);
        LearnerClassMembership::factory()->create(['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'class_group_id' => $classGroup->id, 'starts_on' => '2026-01-01', 'ends_on' => null]);

        return $enrolment;
    }
}
