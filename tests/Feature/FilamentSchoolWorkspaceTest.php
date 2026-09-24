<?php

namespace Tests\Feature;

use App\Filament\School\Pages\AcademicStructure;
use App\Filament\School\Pages\SchoolStaffDirectory;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\SchoolRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSchoolWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_staff_can_review_their_school_staff_directory(): void
    {
        $school = School::factory()->create(['slug' => 'staff-school', 'name' => 'Staff School']);
        $teacher = User::factory()->create(['name' => 'Visible Teacher']);
        $invitee = User::factory()->create(['email' => 'pending@example.test']);

        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $school->invitations()->create([
            'inviter_user_id' => $teacher->id,
            'invitee_user_id' => $invitee->id,
            'email' => $invitee->email,
            'role' => SchoolRole::Bursar,
            'token_hash' => hash('sha256', 'staff-school-invite'),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($teacher)
            ->get('/school/staff-school/school-staff-directory')
            ->assertOk()
            ->assertSee('Staff directory')
            ->assertSee('Visible Teacher')
            ->assertSee('pending@example.test')
            ->assertDontSee(route('schools.overview', $school), false);
    }

    public function test_school_workspace_pages_are_isolated_to_the_selected_tenant(): void
    {
        $teacher = User::factory()->create();
        $firstSchool = School::factory()->create(['slug' => 'first-school', 'name' => 'First School']);
        $secondSchool = School::factory()->create(['slug' => 'second-school', 'name' => 'Second School']);

        foreach ([$firstSchool, $secondSchool] as $school) {
            $membership = $school->memberships()->create([
                'user_id' => $teacher->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $membership->roles()->create(['role' => SchoolRole::Teacher]);
        }

        AcademicYear::factory()->create(['school_id' => $firstSchool->id, 'name' => 'First Academic Year']);
        AcademicYear::factory()->create(['school_id' => $secondSchool->id, 'name' => 'Second Academic Year']);
        Subject::factory()->create(['school_id' => $firstSchool->id, 'name' => 'First Subject']);
        Subject::factory()->create(['school_id' => $secondSchool->id, 'name' => 'Second Subject']);

        $this->actingAs($teacher)
            ->get('/school/first-school/academic-structure')
            ->assertOk()
            ->assertSee('First School')
            ->assertSee('First Academic Year')
            ->assertSee('First Subject')
            ->assertDontSee('Second Academic Year')
            ->assertDontSee('Second Subject')
            ->assertDontSee(route('schools.academic.index', $firstSchool), false);
    }

    public function test_school_admin_sees_only_authorized_overview_workflows(): void
    {
        $school = School::factory()->create(['slug' => 'admin-workflow-nav-school', 'school_type' => 'mixed']);
        $schoolAdmin = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $schoolAdmin->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $attendanceUrl = route('filament.school.pages.attendance-register', ['tenant' => $school->slug]);
        $communicationsUrl = route('filament.school.pages.school-communications', ['tenant' => $school->slug]);
        $feesUrl = route('filament.school.pages.fee-operations', ['tenant' => $school->slug]);
        $legacyAttendanceUrl = route('schools.attendance.index', $school);
        $legacyCommunicationsUrl = route('schools.communication.index', $school);
        $legacyOverviewUrl = route('schools.overview', $school);

        $this->actingAs($schoolAdmin)
            ->get('/school/admin-workflow-nav-school')
            ->assertOk()
            ->assertSee('School type')
            ->assertSee('mixed')
            ->assertSee('School Admin')
            ->assertSee($attendanceUrl, false)
            ->assertSee($communicationsUrl, false)
            ->assertSee($feesUrl, false)
            ->assertSee('Manage school memberships, roles and invitations.')
            ->assertDontSee($legacyAttendanceUrl, false)
            ->assertDontSee($legacyCommunicationsUrl, false)
            ->assertDontSee($legacyOverviewUrl, false);
    }

    public function test_teacher_sees_attendance_but_not_school_admin_workflows(): void
    {
        $school = School::factory()->create(['slug' => 'teacher-workflow-nav-school']);
        $teacher = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $attendanceUrl = route('filament.school.pages.attendance-register', ['tenant' => $school->slug]);
        $communicationsUrl = route('filament.school.pages.school-communications', ['tenant' => $school->slug]);
        $feesUrl = route('filament.school.pages.fee-operations', ['tenant' => $school->slug]);
        $staffDirectoryUrl = route('filament.school.pages.school-staff-directory', ['tenant' => $school->slug]);

        $this->actingAs($teacher)
            ->get('/school/teacher-workflow-nav-school')
            ->assertOk()
            ->assertSee('Teacher')
            ->assertSee($attendanceUrl, false)
            ->assertDontSee($communicationsUrl, false)
            ->assertDontSee($feesUrl, false)
            ->assertDontSee('Manage school memberships, roles and invitations.')
            ->assertSee(route('filament.school.pages.school-staff-directory', ['tenant' => $school->slug]), false);
    }

    public function test_bursar_sees_no_finance_attendance_or_school_admin_workflows(): void
    {
        $school = School::factory()->create(['slug' => 'bursar-workflow-nav-school']);
        $bursar = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $bursar->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => SchoolRole::Bursar]);
        $attendanceUrl = route('filament.school.pages.attendance-register', ['tenant' => $school->slug]);
        $communicationsUrl = route('filament.school.pages.school-communications', ['tenant' => $school->slug]);
        $feesUrl = route('filament.school.pages.fee-operations', ['tenant' => $school->slug]);
        $legacyAttendanceUrl = route('schools.attendance.index', $school);
        $legacyCommunicationsUrl = route('schools.communication.index', $school);
        $legacyOverviewUrl = route('schools.overview', $school);

        $this->actingAs($bursar)
            ->get('/school/bursar-workflow-nav-school')
            ->assertOk()
            ->assertSee('Bursar')
            ->assertDontSee($attendanceUrl, false)
            ->assertDontSee($communicationsUrl, false)
            ->assertDontSee($feesUrl, false)
            ->assertDontSee('Manage school memberships, roles and invitations.')
            ->assertDontSee($legacyAttendanceUrl, false)
            ->assertDontSee($legacyCommunicationsUrl, false)
            ->assertDontSee($legacyOverviewUrl, false);
    }

    public function test_guardians_cannot_access_school_staff_or_academic_workspace_pages(): void
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
            ->get('/school/guardian-school/school-staff-directory')
            ->assertForbidden();

        $this->actingAs($guardian)
            ->get('/school/guardian-school/academic-structure')
            ->assertForbidden();
    }

    public function test_school_admin_can_create_an_academic_year_from_filament(): void
    {
        $school = School::factory()->create(['slug' => 'academic-admin-school']);
        $admin = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(AcademicStructure::class)
            ->set('academicYearName', '2027')
            ->set('academicYearStartsOn', '2027-01-01')
            ->set('academicYearEndsOn', '2027-12-31')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', [
            'school_id' => $school->id,
            'name' => '2027',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'academic_year.created',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_school_admin_can_create_a_subject_and_duplicate_codes_are_rejected(): void
    {
        $school = School::factory()->create(['slug' => 'subject-admin-school']);
        $admin = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(AcademicStructure::class)
            ->set('subjectName', 'Mathematics')
            ->set('subjectCode', 'math')
            ->call('createSubject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'subject.created',
            'actor_user_id' => $admin->id,
        ]);

        Livewire::test(AcademicStructure::class)
            ->set('subjectName', 'Advanced Mathematics')
            ->set('subjectCode', 'math')
            ->call('createSubject')
            ->assertHasErrors(['subjectCode']);
    }

    public function test_school_admin_can_create_a_term_and_overlapping_dates_are_rejected(): void
    {
        $school = School::factory()->create(['slug' => 'term-admin-school']);
        $admin = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2027',
            'starts_on' => '2027-01-01',
            'ends_on' => '2027-12-31',
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(AcademicStructure::class)
            ->set("termNames.{$academicYear->id}", 'Term 1')
            ->set("termStartsOn.{$academicYear->id}", '2027-01-01')
            ->set("termEndsOn.{$academicYear->id}", '2027-03-31')
            ->call('createTerm', $academicYear->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('terms', [
            'academic_year_id' => $academicYear->id,
            'school_id' => $school->id,
            'name' => 'Term 1',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'term.created',
            'actor_user_id' => $admin->id,
        ]);

        Livewire::test(AcademicStructure::class)
            ->set("termNames.{$academicYear->id}", 'Term 2')
            ->set("termStartsOn.{$academicYear->id}", '2027-03-01')
            ->set("termEndsOn.{$academicYear->id}", '2027-05-31')
            ->call('createTerm', $academicYear->id)
            ->assertHasErrors(["termStartsOn.{$academicYear->id}"]);
    }

    public function test_school_admin_can_create_a_class_group_and_duplicate_names_are_rejected(): void
    {
        $school = School::factory()->create(['slug' => 'class-admin-school']);
        $admin = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2027',
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(AcademicStructure::class)
            ->set("classNames.{$academicYear->id}", 'Grade 5 A')
            ->set("classGradeLevels.{$academicYear->id}", 'Grade 5')
            ->set("classStreams.{$academicYear->id}", 'A')
            ->call('createClassGroup', $academicYear->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_groups', [
            'academic_year_id' => $academicYear->id,
            'school_id' => $school->id,
            'name' => 'Grade 5 A',
            'grade_level' => 'Grade 5',
            'stream' => 'A',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'class_group.created',
            'actor_user_id' => $admin->id,
        ]);

        Livewire::test(AcademicStructure::class)
            ->set("classNames.{$academicYear->id}", 'Grade 5 A')
            ->set("classGradeLevels.{$academicYear->id}", 'Grade 5')
            ->call('createClassGroup', $academicYear->id)
            ->assertHasErrors(["classNames.{$academicYear->id}"]);
    }

    public function test_school_admin_can_assign_an_active_school_teacher(): void
    {
        $school = School::factory()->create(['slug' => 'assignment-admin-school']);
        $admin = User::factory()->create();
        $teacher = User::factory()->create(['name' => 'Assigned Teacher']);
        $adminMembership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $adminMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $teacherMembership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $teacherMembership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027']);
        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Grade 5 A',
        ]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(AcademicStructure::class)
            ->set('assignmentClassGroupId', $classGroup->id)
            ->set('assignmentSubjectId', $subject->id)
            ->set('assignmentTeacherUserId', $teacher->id)
            ->call('createTeachingAssignment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teaching_assignments', [
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'teaching_assignment.created',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_school_admin_can_create_a_staff_invitation_from_filament(): void
    {
        $school = School::factory()->create(['slug' => 'invitation-admin-school']);
        $admin = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'new-teacher@example.test']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(SchoolStaffDirectory::class)
            ->set('inviteeEmail', $invitee->email)
            ->set('inviteeRole', SchoolRole::Teacher->value)
            ->call('createInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_invitations', [
            'school_id' => $school->id,
            'inviter_user_id' => $admin->id,
            'invitee_user_id' => $invitee->id,
            'email' => $invitee->email,
            'role' => SchoolRole::Teacher->value,
            'accepted_at' => null,
            'revoked_at' => null,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'school.invitation.created',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_school_admin_can_assign_and_remove_staff_roles_from_filament(): void
    {
        $school = School::factory()->create(['slug' => 'role-admin-school']);
        $admin = User::factory()->create();
        $staff = User::factory()->create(['name' => 'Role Managed Staff']);
        $adminMembership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $adminMembership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $staffMembership = $school->memberships()->create([
            'user_id' => $staff->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(SchoolStaffDirectory::class)
            ->set("roleToAssign.{$staffMembership->id}", SchoolRole::Teacher->value)
            ->call('assignRole', $staffMembership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_role_assignments', [
            'school_membership_id' => $staffMembership->id,
            'role' => SchoolRole::Teacher->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'school.role.assigned',
            'actor_user_id' => $admin->id,
        ]);

        Livewire::test(SchoolStaffDirectory::class)
            ->call('removeRole', $staffMembership->id, SchoolRole::Teacher->value)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('school_role_assignments', [
            'school_membership_id' => $staffMembership->id,
            'role' => SchoolRole::Teacher->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'school.role.removed',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_school_admin_can_revoke_a_pending_invitation_from_filament(): void
    {
        $school = School::factory()->create(['slug' => 'revoke-admin-school']);
        $admin = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'revoke-me@example.test']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $invitation = $school->invitations()->create([
            'inviter_user_id' => $admin->id,
            'invitee_user_id' => $invitee->id,
            'email' => $invitee->email,
            'role' => SchoolRole::Teacher,
            'token_hash' => hash('sha256', 'revoke-me'),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(SchoolStaffDirectory::class)
            ->call('revokeInvitation', $invitation->id)
            ->assertHasNoErrors();

        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'school.invitation.revoked',
            'actor_user_id' => $admin->id,
        ]);
    }
}
