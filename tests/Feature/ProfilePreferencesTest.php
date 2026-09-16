<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_students_are_redirected_to_email_verification_before_dashboard_access(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_profile_preferences_are_updated_without_allowing_role_changes(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Student',
                'email' => $user->email,
                'preferred_subjects' => 'Biology, Chemistry, Mathematics',
                'daily_study_goal_minutes' => 90,
                'timezone' => 'Africa/Nairobi',
                'role' => UserRole::Admin->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame(UserRole::Student, $user->role);
        $this->assertSame([
            'preferred_subjects' => ['Biology', 'Chemistry', 'Mathematics'],
            'daily_study_goal_minutes' => 90,
            'timezone' => 'Africa/Nairobi',
        ], $user->learning_preferences);
    }

    public function test_invalid_profile_photo_and_timezone_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
                'timezone' => 'Not/A-Timezone',
            ])
            ->assertSessionHasErrors(['photo', 'timezone'])
            ->assertRedirect(route('profile.edit'));
    }

    public function test_profile_photo_is_private_to_an_authenticated_account(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => UploadedFile::fake()->image('profile.png'),
                'timezone' => 'Africa/Nairobi',
            ])
            ->assertSessionHasNoErrors();

        $profilePhotoPath = $user->refresh()->profile_photo_path;

        $this->assertNotNull($profilePhotoPath);
        Storage::disk('local')->assertExists($profilePhotoPath);
        $this->actingAs($user)->get(route('profile.photo'))->assertOk();
        $this->post(route('logout'));
        $this->get(route('profile.photo'))->assertRedirect(route('login'));
    }
}
