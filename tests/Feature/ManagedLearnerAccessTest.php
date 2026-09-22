<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\LearnerActivation;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagedLearnerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_and_learner_can_activate_restricted_access(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $learner = Enrolment::factory()->for($school)->create();

        $response = $this->actingAs($admin)->post(route('schools.learners.access.store', [$school, $learner]));
        $activationUrl = session('activation_url');
        $managedUser = User::query()->where('account_type', 'managed_learner')->firstOrFail();

        $this->assertNotNull($activationUrl);
        $this->assertNotNull($managedUser->learner_login_id);
        $this->assertNull($managedUser->email);
        $this->assertSame($managedUser->id, $learner->learnerProfile->fresh()->user_id);

        $token = Str::afterLast($activationUrl, '/');
        $this->get($activationUrl)->assertOk()->assertSee('Activate learner access');
        $this->post(route('learner.activate.store', $token), [
            'token' => $token,
            'password' => 'learner-password',
            'password_confirmation' => 'learner-password',
        ])->assertRedirectToRoute('learner.dashboard');

        $this->assertAuthenticatedAs($managedUser->fresh());
        $this->assertNotNull($managedUser->fresh()->learner_activated_at);
        $this->assertNotNull(LearnerActivation::query()->firstOrFail()->fresh()->used_at);
        $this->get(route('learner.dashboard'))->assertOk()->assertSee($learner->learnerProfile->first_name);
    }

    public function test_managed_learner_can_sign_out_and_sign_in_with_login_id(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $learner = Enrolment::factory()->for($school)->create();
        $activationUrl = $this->issueAndActivate($admin, $school, $learner, 'learner-password');
        $managedUser = User::query()->where('account_type', 'managed_learner')->firstOrFail();

        $this->assertNotEmpty($activationUrl);
        $this->post(route('learner.logout'))->assertRedirectToRoute('learner.login');
        $this->post(route('learner.login'), [
            'learner_login_id' => $managedUser->learner_login_id,
            'password' => 'learner-password',
        ])->assertRedirectToRoute('learner.dashboard');
        $this->get(route('learner.dashboard'))->assertOk();
    }

    public function test_activation_is_one_time_and_expired_links_are_rejected(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $learner = Enrolment::factory()->for($school)->create();
        $response = $this->actingAs($admin)->post(route('schools.learners.access.store', [$school, $learner]));
        $token = Str::afterLast(session('activation_url'), '/');

        $this->post(route('learner.activate.store', $token), [
            'token' => $token,
            'password' => 'learner-password',
            'password_confirmation' => 'learner-password',
        ])->assertRedirectToRoute('learner.dashboard');
        $this->post(route('learner.activate.store', $token), [
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('token');

        $otherLearner = Enrolment::factory()->for($school)->create();
        $expiredResponse = $this->actingAs($admin)->post(route('schools.learners.access.store', [$school, $otherLearner]));
        $expiredToken = Str::afterLast(session('activation_url'), '/');
        LearnerActivation::query()->where('learner_profile_id', $otherLearner->learner_profile_id)->update(['expires_at' => now()->subMinute()]);

        $this->get(route('learner.activate.show', $expiredToken))->assertNotFound();
    }

    public function test_only_school_admin_can_issue_managed_access_and_cross_school_access_is_hidden(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);
        $learner = Enrolment::factory()->for($school)->create();

        $this->actingAs($teacher)
            ->post(route('schools.learners.access.store', [$school, $learner]))
            ->assertForbidden();

        $otherSchool = School::factory()->create();
        $otherLearner = Enrolment::factory()->for($otherSchool)->create();
        $this->actingAs($teacher)
            ->get(route('schools.learners.show', [$school, $otherLearner]))
            ->assertNotFound();
    }

    public function test_managed_learner_cannot_use_adult_or_school_routes(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $learner = Enrolment::factory()->for($school)->create();
        $this->issueAndActivate($admin, $school, $learner, 'learner-password');

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('profile.edit'))->assertForbidden();
        $this->get(route('schools.overview', $school))->assertForbidden();
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
        $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => $role]);

        return [$user, $school];
    }

    private function issueAndActivate(User $admin, School $school, Enrolment $learner, string $password): string
    {
        $response = $this->actingAs($admin)->post(route('schools.learners.access.store', [$school, $learner]));
        $activationUrl = session('activation_url');
        $token = Str::afterLast($activationUrl, '/');

        $this->post(route('learner.activate.store', $token), [
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirectToRoute('learner.dashboard');

        return $activationUrl;
    }
}
