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

class GuardianAttendancePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_can_switch_between_linked_children_without_sibling_attendance_leakage(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $firstLearner = $this->admit($admin, $school, 'GA-001');
        $secondLearner = $this->admit($admin, $school, 'GA-002');
        $this->linkGuardian($admin, $school, $firstLearner, $guardian);
        $this->linkGuardian($admin, $school, $secondLearner, $guardian);
        [$classGroup, $assignment, $teacher] = $this->attendanceSetup($school);
        $this->placeLearner($school, $classGroup, $firstLearner);
        $this->placeLearner($school, $classGroup, $secondLearner);
        $this->recordAttendance($school, $classGroup, $assignment, $teacher, $firstLearner, 'present', '2026-09-22');
        $this->recordAttendance($school, $classGroup, $assignment, $teacher, $secondLearner, 'absent', '2026-09-23');

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index', ['learner' => $firstLearner->id]))
            ->assertOk()
            ->assertSee('GA-001')
            ->assertSee('present')
            ->assertDontSee('absent');

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index', ['learner' => $secondLearner->id]))
            ->assertOk()
            ->assertSee('GA-002')
            ->assertSee('absent')
            ->assertDontSee('present');
    }

    public function test_revoked_or_unknown_child_selection_returns_not_found(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $learner = $this->admit($admin, $school, 'GA-003');
        $this->linkGuardian($admin, $school, $learner, $guardian);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index', ['learner' => 999999]))
            ->assertNotFound();

        $link = $guardian->guardianLinks()->where('enrolment_id', $learner->id)->firstOrFail();
        $link->update(['status' => 'revoked', 'revoked_at' => now()]);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index', ['learner' => $learner->id]))
            ->assertNotFound();
    }

    public function test_guardian_sees_no_attendance_for_an_inactive_enrolment(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $learner = $this->admit($admin, $school, 'GA-004');
        $this->linkGuardian($admin, $school, $learner, $guardian);
        $learner->update(['status' => 'withdrawn']);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('No active learner relationships are available.')
            ->assertDontSee('GA-004');
    }

    /** @return array{0: User, 1: School} */
    private function schoolAdmin(): array
    {
        $admin = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        return [$admin, $school];
    }

    private function admit(User $admin, School $school, string $admissionNumber): Enrolment
    {
        $this->actingAs($admin)->post(route('schools.learners.store', $school), ['first_name' => 'Guardian', 'last_name' => 'Learner', 'admission_number' => $admissionNumber])->assertRedirect();

        return Enrolment::query()->where('school_id', $school->id)->where('admission_number', $admissionNumber)->firstOrFail();
    }

    private function linkGuardian(User $admin, School $school, Enrolment $enrolment, User $guardian): void
    {
        $this->actingAs($admin)->post(route('schools.learners.guardians.store', [$school, $enrolment]), ['email' => $guardian->email, 'relationship' => 'Parent'])->assertRedirect();
    }

    /** @return array{0: ClassGroup, 1: TeachingAssignment, 2: User} */
    private function attendanceSetup(School $school): array
    {
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->for($school)->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $classGroup = ClassGroup::factory()->for($school)->for($academicYear)->create();
        $subject = Subject::factory()->for($school)->create();
        $assignment = TeachingAssignment::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id, 'subject_id' => $subject->id, 'teacher_user_id' => $teacher->id]);

        return [$classGroup, $assignment, $teacher];
    }

    private function placeLearner(School $school, ClassGroup $classGroup, Enrolment $enrolment): void
    {
        LearnerClassMembership::factory()->create(['school_id' => $school->id, 'enrolment_id' => $enrolment->id, 'class_group_id' => $classGroup->id, 'starts_on' => '2026-01-01']);
    }

    private function recordAttendance(School $school, ClassGroup $classGroup, TeachingAssignment $assignment, User $teacher, Enrolment $enrolment, string $status, string $date): void
    {
        $session = AttendanceSession::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id, 'teaching_assignment_id' => $assignment->id, 'session_date' => $date, 'version' => 1, 'created_by_user_id' => $teacher->id, 'updated_by_user_id' => $teacher->id]);
        AttendanceEntry::factory()->create(['attendance_session_id' => $session->id, 'enrolment_id' => $enrolment->id, 'status' => $status, 'marked_by_user_id' => $teacher->id, 'marked_at' => now()]);
    }
}
