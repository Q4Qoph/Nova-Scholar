<?php

namespace Tests\Feature;

use App\Filament\School\Pages\LearnerDetail;
use App\Filament\School\Pages\LearnerImportReview;
use App\Filament\School\Pages\LearnerRegistry;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\ImportBatch;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLearnerRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_staff_can_view_only_the_selected_school_learner_registry(): void
    {
        $teacher = User::factory()->create();
        $firstSchool = School::factory()->create(['slug' => 'registry-school', 'name' => 'Registry School']);
        $secondSchool = School::factory()->create(['slug' => 'other-registry-school', 'name' => 'Other Registry School']);
        $membership = $firstSchool->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $firstLearner = Enrolment::factory()->for($firstSchool)->create(['admission_number' => 'REG-001']);
        $firstLearner->learnerProfile()->update(['first_name' => 'First', 'last_name' => 'Learner']);
        $secondLearner = Enrolment::factory()->for($secondSchool)->create(['admission_number' => 'OTHER-001']);
        $secondLearner->learnerProfile()->update(['first_name' => 'Other', 'last_name' => 'Learner']);

        $this->actingAs($teacher)
            ->get('/school/registry-school/learner-registry')
            ->assertOk()
            ->assertSee('Registry School')
            ->assertSee('First Learner')
            ->assertSee('REG-001')
            ->assertDontSee('Other Learner')
            ->assertDontSee('OTHER-001')
            ->assertDontSee(route('schools.learners.index', $firstSchool), false);
    }

    public function test_school_staff_can_paginate_only_selected_school_learners_in_the_filament_registry(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'paginated-registry-school']);
        $otherSchool = School::factory()->create(['slug' => 'other-paginated-registry-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $olderLearner = Enrolment::factory()->for($school)->create(['admission_number' => 'PAGE-OLD']);
        $olderLearner->learnerProfile()->update(['first_name' => 'Older', 'last_name' => 'Learner']);

        for ($index = 1; $index <= 50; $index++) {
            Enrolment::factory()->for($school)->create([
                'admission_number' => sprintf('PAGE-%03d', $index),
            ]);
        }

        Enrolment::factory()->for($otherSchool)->create(['admission_number' => 'OTHER-PAGE-001']);

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerRegistry::class)
            ->assertSee('Showing')
            ->assertSee('51')
            ->assertSee('PAGE-050')
            ->assertDontSee('PAGE-OLD')
            ->assertDontSee('OTHER-PAGE-001')
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->assertSee('PAGE-OLD')
            ->assertDontSee('PAGE-050')
            ->assertDontSee('OTHER-PAGE-001');
    }

    public function test_guardian_cannot_access_the_filament_learner_registry(): void
    {
        $guardian = User::factory()->create();
        $school = School::factory()->create(['slug' => 'guardian-registry-school']);
        $membership = $school->memberships()->create([
            'user_id' => $guardian->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Guardian]);

        $this->actingAs($guardian)
            ->get('/school/guardian-registry-school/learner-registry')
            ->assertForbidden();
    }

    public function test_school_staff_can_view_a_tenant_scoped_learner_detail(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'detail-school', 'name' => 'Detail School']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $learner = Enrolment::factory()->for($school)->create(['admission_number' => 'DET-001']);
        $learner->learnerProfile()->update(['first_name' => 'Detail', 'last_name' => 'Learner']);

        $this->actingAs($teacher)
            ->get('/school/detail-school/learner/'.$learner->id)
            ->assertOk()
            ->assertSee('Detail Learner')
            ->assertSee('DET-001')
            ->assertDontSee('Manage learner')
            ->assertDontSee(route('schools.learners.show', [$school, $learner]), false);
    }

    public function test_school_staff_cannot_view_a_learner_from_another_school_through_the_detail_route(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'scoped-detail-school']);
        $otherSchool = School::factory()->create(['slug' => 'other-detail-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $learner = Enrolment::factory()->for($otherSchool)->create();

        $this->actingAs($teacher)
            ->get('/school/scoped-detail-school/learner/'.$learner->id)
            ->assertNotFound();
    }

    public function test_school_admin_can_admit_a_learner_from_the_filament_registry(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'admission-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);

        for ($index = 1; $index <= 51; $index++) {
            Enrolment::factory()->for($school)->create([
                'admission_number' => sprintf('EXISTING-%03d', $index),
            ]);
        }

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(LearnerRegistry::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('firstName', 'New')
            ->set('lastName', 'Learner')
            ->set('preferredName', 'Nova')
            ->set('dateOfBirth', '2014-05-12')
            ->set('admissionNumber', 'ADM-001')
            ->call('admitLearner')
            ->assertSet('paginators.page', 1)
            ->assertSee('ADM-001')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('learner_profiles', [
            'first_name' => 'New',
            'last_name' => 'Learner',
            'preferred_name' => 'Nova',
        ]);
        $this->assertDatabaseHas('enrolments', [
            'school_id' => $school->id,
            'admission_number' => 'ADM-001',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'learner.admitted',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_admit_a_learner_from_the_filament_registry(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'teacher-admission-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerRegistry::class)
            ->set('firstName', 'Blocked')
            ->set('lastName', 'Learner')
            ->set('admissionNumber', 'BLOCK-001')
            ->call('admitLearner')
            ->assertForbidden();

        $this->assertDatabaseMissing('enrolments', ['admission_number' => 'BLOCK-001']);
    }

    public function test_school_admin_can_link_and_revoke_a_guardian_from_learner_detail(): void
    {
        $admin = User::factory()->create();
        $guardian = User::factory()->create(['email' => 'guardian-detail@example.test']);
        $school = School::factory()->create(['slug' => 'guardian-detail-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $learner = Enrolment::factory()->for($school)->create();

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('guardianEmail', $guardian->email)
            ->set('guardianRelationship', 'Parent')
            ->call('linkGuardian')
            ->assertHasNoErrors();

        $link = GuardianLink::query()->where('enrolment_id', $learner->id)->firstOrFail();
        $this->assertSame('active', $link->status);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'guardian.linked',
            'auditable_id' => $link->id,
        ]);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->call('revokeGuardian', $link->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('guardian_links', [
            'id' => $link->id,
            'status' => 'revoked',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'guardian.link.revoked',
            'auditable_id' => $link->id,
        ]);
    }

    public function test_teacher_cannot_manage_guardians_from_learner_detail(): void
    {
        $teacher = User::factory()->create();
        $guardian = User::factory()->create(['email' => 'blocked-guardian@example.test']);
        $school = School::factory()->create(['slug' => 'blocked-guardian-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $learner = Enrolment::factory()->for($school)->create();

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('guardianEmail', $guardian->email)
            ->set('guardianRelationship', 'Parent')
            ->call('linkGuardian')
            ->assertForbidden();

        $this->assertDatabaseMissing('guardian_links', [
            'enrolment_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
        ]);
    }

    public function test_school_admin_can_issue_managed_learner_access_from_detail(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'managed-access-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $learner = Enrolment::factory()->for($school)->create();

        $this->actingAs($admin);
        Filament::setTenant($school);

        $component = Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->call('issueManagedAccess')
            ->assertHasNoErrors();

        $this->assertNotNull($component->get('activationUrl'));
        $this->assertDatabaseHas('learner_profiles', [
            'id' => $learner->learner_profile_id,
        ]);
        $this->assertDatabaseHas('users', [
            'account_type' => 'managed_learner',
            'learner_login_id' => $learner->learnerProfile->fresh()->user->learner_login_id,
        ]);
        $this->assertDatabaseHas('learner_activations', [
            'school_id' => $school->id,
            'learner_profile_id' => $learner->learner_profile_id,
            'used_at' => null,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'managed_learner_access.issued',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_issue_managed_learner_access_from_detail(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-managed-access-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $learner = Enrolment::factory()->for($school)->create();

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->call('issueManagedAccess')
            ->assertForbidden();

        $this->assertDatabaseMissing('learner_activations', [
            'school_id' => $school->id,
            'learner_profile_id' => $learner->learner_profile_id,
        ]);
    }

    public function test_school_admin_can_deactivate_a_learner_from_detail(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'deactivation-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $learner = Enrolment::factory()->for($school)->create(['status' => 'active']);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('deactivatedOn', '2026-09-22')
            ->call('deactivateLearner')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('enrolments', [
            'id' => $learner->id,
            'status' => 'withdrawn',
        ]);
        $this->assertSame('2026-09-22', $learner->fresh()->withdrawn_at->toDateString());
        $this->assertDatabaseHas('learner_profiles', [
            'id' => $learner->learner_profile_id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'learner.deactivated',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_deactivate_a_learner_from_detail(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-deactivation-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $learner = Enrolment::factory()->for($school)->create(['status' => 'active']);

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('deactivatedOn', '2026-09-22')
            ->call('deactivateLearner')
            ->assertForbidden();

        $this->assertDatabaseHas('enrolments', [
            'id' => $learner->id,
            'status' => 'active',
        ]);
    }

    public function test_school_admin_can_promote_a_learner_from_detail(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'promotion-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $year = AcademicYear::factory()->for($school)->create(['name' => '2026']);
        $currentClass = ClassGroup::factory()->for($school)->for($year, 'academicYear')->create(['name' => 'Grade 5']);
        $nextClass = ClassGroup::factory()->for($school)->for($year, 'academicYear')->create(['name' => 'Grade 6']);
        $learner = Enrolment::factory()->for($school)->create(['status' => 'active']);
        LearnerClassMembership::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'class_group_id' => $currentClass->id,
            'starts_on' => '2026-01-01',
            'ends_on' => null,
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('promotionClassGroupId', $nextClass->id)
            ->set('promotionStartsOn', '2026-09-22')
            ->call('promoteLearner')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('learner_class_memberships', [
            'enrolment_id' => $learner->id,
            'class_group_id' => $currentClass->id,
        ]);
        $this->assertSame('2026-09-21', LearnerClassMembership::query()->where('class_group_id', $currentClass->id)->firstOrFail()->ends_on->toDateString());
        $this->assertDatabaseHas('learner_class_memberships', [
            'enrolment_id' => $learner->id,
            'class_group_id' => $nextClass->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'learner.promoted',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_promote_a_learner_from_detail(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-promotion-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $year = AcademicYear::factory()->for($school)->create(['name' => '2027']);
        $nextClass = ClassGroup::factory()->for($school)->for($year, 'academicYear')->create();
        $learner = Enrolment::factory()->for($school)->create(['status' => 'active']);

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('promotionClassGroupId', $nextClass->id)
            ->set('promotionStartsOn', '2027-01-01')
            ->call('promoteLearner')
            ->assertForbidden();

        $this->assertDatabaseMissing('learner_class_memberships', ['enrolment_id' => $learner->id]);
    }

    public function test_dual_school_admin_can_transfer_a_learner_from_detail(): void
    {
        $admin = User::factory()->create();
        $sourceSchool = School::factory()->create(['slug' => 'transfer-source-school']);
        $destinationSchool = School::factory()->create(['slug' => 'transfer-destination-school']);

        foreach ([$sourceSchool, $destinationSchool] as $school) {
            $membership = $school->memberships()->create([
                'user_id' => $admin->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        }

        $learner = Enrolment::factory()->for($sourceSchool)->create(['status' => 'active', 'admission_number' => 'SRC-001']);

        $this->actingAs($admin);
        Filament::setTenant($sourceSchool);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('transferDestinationSchoolId', $destinationSchool->id)
            ->set('transferAdmissionNumber', 'DST-001')
            ->set('transferDate', '2026-09-22')
            ->call('transferLearner')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('enrolments', [
            'id' => $learner->id,
            'school_id' => $sourceSchool->id,
            'status' => 'withdrawn',
        ]);
        $this->assertDatabaseHas('enrolments', [
            'school_id' => $destinationSchool->id,
            'learner_profile_id' => $learner->learner_profile_id,
            'admission_number' => 'DST-001',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $sourceSchool->id,
            'event_type' => 'learner.transferred_out',
            'actor_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $destinationSchool->id,
            'event_type' => 'learner.transferred_in',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_transfer_a_learner_from_detail(): void
    {
        $teacher = User::factory()->create();
        $sourceSchool = School::factory()->create(['slug' => 'blocked-transfer-source']);
        $destinationSchool = School::factory()->create(['slug' => 'blocked-transfer-destination']);
        $membership = $sourceSchool->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $learner = Enrolment::factory()->for($sourceSchool)->create(['status' => 'active']);

        $this->actingAs($teacher);
        Filament::setTenant($sourceSchool);

        Livewire::test(LearnerDetail::class, ['record' => $learner->id])
            ->set('transferDestinationSchoolId', $destinationSchool->id)
            ->set('transferAdmissionNumber', 'BLOCK-001')
            ->set('transferDate', '2026-09-22')
            ->call('transferLearner')
            ->assertForbidden();

        $this->assertDatabaseMissing('enrolments', [
            'school_id' => $destinationSchool->id,
            'admission_number' => 'BLOCK-001',
        ]);
    }

    public function test_school_admin_can_stage_a_learner_csv_from_the_filament_registry(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'import-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $csv = UploadedFile::fake()->createWithContent(
            'learners.csv',
            "first_name,last_name,preferred_name,date_of_birth,admission_number\n".
            "Imported,Learner,Nova,2014-05-12,IMP-001\n",
        );

        $this->actingAs($admin);
        Filament::setTenant($school);

        $component = Livewire::test(LearnerRegistry::class)
            ->set('importFile', $csv)
            ->call('stageLearnerImport')
            ->assertHasNoErrors();

        $this->assertNotNull($component->get('importReviewUrl'));
        $this->assertDatabaseHas('import_batches', [
            'school_id' => $school->id,
            'source_filename' => 'learners.csv',
            'status' => 'staged',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
        ]);
        $this->assertDatabaseHas('import_rows', [
            'row_number' => 2,
            'status' => 'valid',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'learner_import.staged',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_stage_a_learner_csv_from_the_filament_registry(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-import-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $csv = UploadedFile::fake()->createWithContent(
            'blocked.csv',
            "first_name,last_name,preferred_name,date_of_birth,admission_number\n".
            "Blocked,Learner,,,BLOCK-001\n",
        );

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerRegistry::class)
            ->set('importFile', $csv)
            ->call('stageLearnerImport')
            ->assertForbidden();

        $this->assertDatabaseMissing('import_batches', ['school_id' => $school->id]);
    }

    public function test_school_admin_can_review_and_commit_a_staged_import_in_filament(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->create(['slug' => 'import-review-school']);
        $membership = $school->memberships()->create([
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::SchoolAdmin]);
        $csv = UploadedFile::fake()->createWithContent(
            'review.csv',
            "first_name,last_name,preferred_name,date_of_birth,admission_number\n".
            "Review,Learner,Nova,2014-05-12,REV-001\n",
        );

        $this->actingAs($admin);
        Filament::setTenant($school);

        Livewire::test(LearnerRegistry::class)
            ->set('importFile', $csv)
            ->call('stageLearnerImport')
            ->assertHasNoErrors();

        $batch = ImportBatch::query()->where('school_id', $school->id)->firstOrFail();

        Livewire::test(LearnerImportReview::class, ['record' => $batch->id])
            ->call('commitImport')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('import_batches', [
            'id' => $batch->id,
            'status' => 'committed',
        ]);
        $this->assertDatabaseHas('import_rows', [
            'import_batch_id' => $batch->id,
            'status' => 'committed',
        ]);
        $this->assertDatabaseHas('enrolments', [
            'school_id' => $school->id,
            'admission_number' => 'REV-001',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'learner_import.committed',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_teacher_cannot_review_a_staged_import_in_filament(): void
    {
        $teacher = User::factory()->create();
        $school = School::factory()->create(['slug' => 'blocked-import-review-school']);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $batch = ImportBatch::factory()->for($school)->create();

        $this->actingAs($teacher);
        Filament::setTenant($school);

        Livewire::test(LearnerImportReview::class, ['record' => $batch->id])
            ->assertForbidden();
    }
}
