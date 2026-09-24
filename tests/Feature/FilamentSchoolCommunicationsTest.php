<?php

namespace Tests\Feature;

use App\Filament\School\Pages\SchoolCommunications;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\CreateSchoolAnnouncement;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSchoolCommunicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_draft_and_send_a_notice_idempotently_in_filament(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $learner = Enrolment::factory()->for($school)->create();
        GuardianLink::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'verified_by_user_id' => $admin->id,
        ]);
        $this->setTenant($school, $admin);

        $this->get('/school/'.$school->slug.'/school-communications')
            ->assertOk()
            ->assertSee('Draft a guardian notice');

        $component = Livewire::test(SchoolCommunications::class)
            ->set('audienceType', 'all_guardians')
            ->set('draftTitle', 'Term dates')
            ->set('messageBody', 'The new term starts on Monday.')
            ->call('createDraft')
            ->assertHasNoErrors();

        $announcement = $school->announcements()->sole();
        $this->assertSame('draft', $announcement->status);

        $component->call('sendAnnouncement', $announcement->id)
            ->assertHasNoErrors()
            ->call('sendAnnouncement', $announcement->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'school_id' => $school->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('message_deliveries', [
            'announcement_id' => $announcement->id,
            'school_id' => $school->id,
            'guardian_user_id' => $guardian->id,
            'channel' => 'in_app',
            'status' => 'sent',
        ]);
        $this->assertDatabaseCount('message_deliveries', 1);
        $this->assertDatabaseCount('audit_events', 1);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('Term dates')
            ->assertSee('The new term starts on Monday.');
    }

    public function test_class_notice_is_limited_to_guardians_of_current_class_members(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $targetClass = $this->classGroup($school, 'Grade 5 A');
        $otherClass = $this->classGroup($school, 'Grade 5 B');
        $targetGuardian = User::factory()->create();
        $otherGuardian = User::factory()->create();
        $targetLearner = Enrolment::factory()->for($school)->create();
        $otherLearner = Enrolment::factory()->for($school)->create();
        $this->placeLearner($school, $targetClass, $targetLearner);
        $this->placeLearner($school, $otherClass, $otherLearner);
        $this->linkGuardian($admin, $school, $targetLearner, $targetGuardian);
        $this->linkGuardian($admin, $school, $otherLearner, $otherGuardian);
        $this->setTenant($school, $admin);

        Livewire::test(SchoolCommunications::class)
            ->set('audienceType', 'class_guardians')
            ->set('classGroupId', $targetClass->id)
            ->set('draftTitle', 'Grade 5 A update')
            ->set('messageBody', 'This message is for Grade 5 A.')
            ->call('createDraft')
            ->assertHasNoErrors()
            ->call('sendAnnouncement', $school->announcements()->sole()->id)
            ->assertHasNoErrors();

        $announcement = $school->announcements()->sole();
        $this->assertDatabaseHas('message_deliveries', [
            'announcement_id' => $announcement->id,
            'guardian_user_id' => $targetGuardian->id,
        ]);
        $this->assertDatabaseMissing('message_deliveries', [
            'announcement_id' => $announcement->id,
            'guardian_user_id' => $otherGuardian->id,
        ]);
    }

    public function test_non_admin_cannot_open_the_communications_page_or_create_notices(): void
    {
        [$school, $teacher] = $this->schoolContext(SchoolRole::Teacher);
        $this->setTenant($school, $teacher);

        $this->get('/school/'.$school->slug.'/school-communications')
            ->assertForbidden();

        try {
            app(CreateSchoolAnnouncement::class)->handle($teacher, $school, [
                'audience_type' => 'all_guardians',
                'title' => 'Blocked notice',
                'body' => 'Blocked',
            ]);
            $this->fail('A teacher must not create school announcements.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('This action is unauthorized.', $exception->getMessage());
        }

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_foreign_class_and_announcement_ids_are_rejected(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        [$otherSchool, $otherAdmin] = $this->schoolAdmin();
        $foreignClass = $this->classGroup($otherSchool, 'Foreign class');
        $foreignAnnouncement = $otherSchool->announcements()->create([
            'created_by_user_id' => $otherAdmin->id,
            'audience_type' => 'all_guardians',
            'title' => 'Private notice',
            'body' => 'Not for this school.',
            'status' => 'draft',
        ]);
        $this->setTenant($school, $admin);

        Livewire::test(SchoolCommunications::class)
            ->set('audienceType', 'class_guardians')
            ->set('classGroupId', $foreignClass->id)
            ->set('draftTitle', 'Forged class notice')
            ->set('messageBody', 'This must not be created.')
            ->call('createDraft')
            ->assertHasErrors('classGroupId');

        try {
            Livewire::test(SchoolCommunications::class)
                ->call('sendAnnouncement', $foreignAnnouncement->id);
            $this->fail('A foreign school announcement must not be resolved.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(Announcement::class, $exception->getModel());
        }

        $this->assertDatabaseCount('announcements', 1);
        $this->assertSame('draft', $foreignAnnouncement->fresh()->status);
    }

    /** @return array{School, User} */
    private function schoolAdmin(): array
    {
        return $this->schoolContext(SchoolRole::SchoolAdmin);
    }

    /** @return array{School, User} */
    private function schoolContext(SchoolRole $role): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => $role]);

        return [$school, $user];
    }

    private function setTenant(School $school, User $user): void
    {
        $this->actingAs($user);
        Filament::setTenant($school);
    }

    private function classGroup(School $school, string $name): ClassGroup
    {
        $academicYear = AcademicYear::factory()->for($school)->create([
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ]);

        return ClassGroup::factory()->for($school)->for($academicYear)->create(['name' => $name]);
    }

    private function placeLearner(School $school, ClassGroup $classGroup, Enrolment $learner): void
    {
        LearnerClassMembership::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'class_group_id' => $classGroup->id,
            'starts_on' => today(),
        ]);
    }

    private function linkGuardian(User $admin, School $school, Enrolment $learner, User $guardian): void
    {
        GuardianLink::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'verified_by_user_id' => $admin->id,
        ]);
    }
}
