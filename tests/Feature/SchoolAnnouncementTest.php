<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\LearnerClassMembership;
use App\Models\MessageDelivery;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_open_notice_index_with_active_classes(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $classGroup = $this->classGroup($school, 'Grade 5 A');

        $this->actingAs($admin)
            ->get(route('schools.communication.index', $school))
            ->assertOk()
            ->assertSee($classGroup->name);
    }

    public function test_school_admin_can_save_send_and_redeliver_a_notice_to_all_active_guardians(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $guardian = User::factory()->create();
        $learner = Enrolment::factory()->for($school)->create();
        GuardianLink::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'verified_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('schools.announcements.store', $school), [
                'audience_type' => 'all_guardians',
                'title' => 'Term dates',
                'body' => 'The new term starts on Monday.',
            ])
            ->assertRedirectToRoute('schools.communication.index', $school);

        $announcement = Announcement::query()->firstOrFail();
        $sendRoute = route('schools.announcements.send', [$school, $announcement]);

        $this->actingAs($admin)->post($sendRoute)->assertRedirectToRoute('schools.communication.index', $school);
        $this->actingAs($admin)->post($sendRoute)->assertRedirectToRoute('schools.communication.index', $school);

        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'status' => 'sent']);
        $this->assertDatabaseCount('message_deliveries', 1);
        $this->assertDatabaseHas('message_deliveries', [
            'announcement_id' => $announcement->id,
            'guardian_user_id' => $guardian->id,
            'channel' => 'in_app',
            'status' => 'sent',
        ]);

        $this->actingAs($guardian)
            ->get(route('guardian.learners.index'))
            ->assertOk()
            ->assertSee('Term dates')
            ->assertSee('The new term starts on Monday.')
            ->assertSee('Delivered');
    }

    public function test_class_notice_only_reaches_guardians_of_current_class_members(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $firstClass = $this->classGroup($school, 'Grade 5 A');
        $secondClass = $this->classGroup($school, 'Grade 5 B');
        $firstGuardian = User::factory()->create();
        $secondGuardian = User::factory()->create();
        $firstLearner = Enrolment::factory()->for($school)->create();
        $secondLearner = Enrolment::factory()->for($school)->create();

        $this->placeLearner($school, $firstClass, $firstLearner);
        $this->placeLearner($school, $secondClass, $secondLearner);
        $this->linkGuardian($admin, $school, $firstLearner, $firstGuardian);
        $this->linkGuardian($admin, $school, $secondLearner, $secondGuardian);

        $this->actingAs($admin)->post(route('schools.announcements.store', $school), [
            'audience_type' => 'class_guardians',
            'class_group_id' => $firstClass->id,
            'title' => 'Grade 5 A update',
            'body' => 'This message is for Grade 5 A.',
        ])->assertRedirect();

        $announcement = Announcement::query()->firstOrFail();
        $this->actingAs($admin)
            ->post(route('schools.announcements.send', [$school, $announcement]))
            ->assertRedirect();

        $this->assertDatabaseHas('message_deliveries', [
            'announcement_id' => $announcement->id,
            'guardian_user_id' => $firstGuardian->id,
        ]);
        $this->assertDatabaseMissing('message_deliveries', [
            'announcement_id' => $announcement->id,
            'guardian_user_id' => $secondGuardian->id,
        ]);
        $this->assertSame(1, MessageDelivery::query()->where('announcement_id', $announcement->id)->count());
    }

    public function test_only_school_admins_can_manage_school_notices(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);

        $this->actingAs($teacher)
            ->get(route('schools.communication.index', $school))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('schools.announcements.store', $school), [
                'audience_type' => 'all_guardians',
                'title' => 'Blocked',
                'body' => 'Blocked',
            ])
            ->assertForbidden();
    }

    /** @return array{0: User, 1: School} */
    private function schoolAdmin(): array
    {
        return $this->schoolContext(SchoolRole::SchoolAdmin);
    }

    /** @return array{0: User, 1: School} */
    private function schoolContext(SchoolRole $role): array
    {
        $user = User::factory()->create();
        $school = School::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => $role]);

        return [$user, $school];
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
            'starts_on' => '2026-01-01',
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
