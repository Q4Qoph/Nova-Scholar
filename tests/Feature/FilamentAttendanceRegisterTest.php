<?php

namespace Tests\Feature;

use App\Filament\School\Pages\AttendanceRegister;
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
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAttendanceRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_opens_and_saves_only_their_assigned_class_register(): void
    {
        [$school, $teacher, $assignment, $learner] = $this->attendanceContext();
        $this->actingAs($teacher);
        Filament::setTenant($school);
        $this->get('/school/'.$school->slug.'/attendance-register')->assertOk();

        $component = Livewire::test(AttendanceRegister::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('sessionDate', '2026-09-23')
            ->call('openRegister')
            ->assertHasNoErrors()
            ->assertSet("entries.{$learner->id}.status", 'unmarked');

        $this->assertDatabaseCount('attendance_sessions', 0);

        $component->set("entries.{$learner->id}.status", 'present')
            ->call('saveRegister')
            ->assertHasNoErrors()
            ->assertSet('version', 1);

        $session = AttendanceSession::query()->sole();

        $this->assertDatabaseHas('attendance_entries', [
            'attendance_session_id' => $session->id,
            'enrolment_id' => $learner->id,
            'status' => 'present',
            'marked_by_user_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'attendance_register.saved',
            'auditable_id' => $session->id,
        ]);
    }

    public function test_teacher_cannot_open_another_teachers_assignment_or_a_foreign_tenant(): void
    {
        [$school, $teacher] = $this->attendanceContext();
        $otherTeacher = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $otherTeacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => AcademicYear::factory()->create(['school_id' => $school->id])->id,
        ]);
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => Subject::factory()->create(['school_id' => $school->id])->id,
            'teacher_user_id' => $otherTeacher->id,
        ]);
        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(AttendanceRegister::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('sessionDate', '2026-09-23')
            ->call('openRegister')
            ->assertHasErrors('teachingAssignmentId');

        $otherSchool = School::factory()->create(['slug' => 'attendance-other-school']);
        Filament::setTenant($otherSchool);

        $this->actingAs($teacher)
            ->get('/school/attendance-other-school/attendance-register')
            ->assertNotFound();

        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    public function test_livewire_save_rejects_a_foreign_learner_id(): void
    {
        [$school, $teacher, $assignment] = $this->attendanceContext();
        $foreignSchool = School::factory()->create();
        $foreignLearner = Enrolment::factory()->create(['school_id' => $foreignSchool->id]);
        $this->actingAs($teacher);
        Filament::setTenant($school);

        $component = Livewire::test(AttendanceRegister::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('sessionDate', '2026-09-23')
            ->call('openRegister');

        $component->set("entries.{$foreignLearner->id}.status", 'present')
            ->call('saveRegister')
            ->assertHasErrors('entries');

        $this->assertDatabaseMissing('attendance_entries', [
            'enrolment_id' => $foreignLearner->id,
        ]);
    }

    public function test_status_corrections_require_a_reason_and_preserve_history(): void
    {
        [$school, $admin, $assignment, $learner] = $this->attendanceContext(SchoolRole::SchoolAdmin);
        $this->actingAs($admin);
        Filament::setTenant($school);

        $component = Livewire::test(AttendanceRegister::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('sessionDate', '2026-09-23')
            ->call('openRegister')
            ->set("entries.{$learner->id}.status", 'present')
            ->call('saveRegister')
            ->assertHasNoErrors();

        $component->assertSet('version', 1);

        $component->set("entries.{$learner->id}.status", 'late')
            ->call('saveRegister')
            ->assertHasErrors("entries.{$learner->id}.correction_reason");

        $component->set("entries.{$learner->id}.correction_reason", 'Teacher confirmed late arrival.')
            ->call('saveRegister')
            ->assertHasNoErrors();

        $session = AttendanceSession::query()->sole();
        $this->assertDatabaseHas('attendance_entries', [
            'attendance_session_id' => $session->id,
            'enrolment_id' => $learner->id,
            'status' => 'late',
        ]);
        $this->assertDatabaseHas('attendance_entry_corrections', [
            'attendance_session_id' => $session->id,
            'from_status' => 'present',
            'to_status' => 'late',
            'reason' => 'Teacher confirmed late arrival.',
        ]);
    }

    public function test_stale_register_version_is_rejected_in_filament(): void
    {
        [$school, $teacher, $assignment, $learner] = $this->attendanceContext();
        $this->actingAs($teacher);
        Filament::setTenant($school);

        $session = $school->attendanceSessions()->create([
            'class_group_id' => $assignment->class_group_id,
            'teaching_assignment_id' => $assignment->id,
            'session_date' => '2026-09-23',
            'status' => 'open',
            'version' => 2,
            'created_by_user_id' => $teacher->id,
            'updated_by_user_id' => $teacher->id,
        ]);
        AttendanceEntry::factory()->create([
            'attendance_session_id' => $session->id,
            'enrolment_id' => $learner->id,
            'status' => 'present',
        ]);

        $component = Livewire::test(AttendanceRegister::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('sessionDate', '2026-09-23')
            ->call('openRegister')
            ->assertSet('version', 2);

        $session->update(['version' => 3]);

        $component
            ->set("entries.{$learner->id}.status", 'late')
            ->set("entries.{$learner->id}.correction_reason", 'Correction after refresh.')
            ->call('saveRegister')
            ->assertHasErrors('version');

        $this->assertDatabaseHas('attendance_entries', [
            'attendance_session_id' => $session->id,
            'enrolment_id' => $learner->id,
            'status' => 'present',
        ]);
    }

    public function test_bursar_cannot_access_the_teacher_attendance_page(): void
    {
        [$school] = $this->attendanceContext();
        $bursar = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $bursar->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Bursar]);

        $this->actingAs($bursar);

        $this->get('/school/'.$school->slug.'/attendance-register')
            ->assertForbidden();
    }

    /** @return array{School, User, TeachingAssignment, Enrolment} */
    private function attendanceContext(SchoolRole $role = SchoolRole::Teacher): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => $role]);
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);
        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $role === SchoolRole::Teacher ? $user->id : User::factory()->create()->id,
        ]);
        $learner = Enrolment::factory()->create(['school_id' => $school->id]);
        LearnerClassMembership::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'class_group_id' => $classGroup->id,
            'starts_on' => '2026-01-01',
        ]);

        return [$school, $user, $assignment, $learner];
    }
}
