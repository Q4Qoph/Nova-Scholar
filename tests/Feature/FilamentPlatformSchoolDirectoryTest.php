<?php

namespace Tests\Feature;

use App\Filament\Platform\Pages\SchoolDirectory;
use App\Models\School;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPlatformSchoolDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_the_school_directory(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $school = School::factory()->create(['name' => 'Mwangaza Demo School']);

        $this->actingAs($admin)
            ->get('/platform/school-directory')
            ->assertOk()
            ->assertSee('Mwangaza Demo School')
            ->assertSee('Provision a school');
    }

    public function test_ordinary_adult_cannot_view_the_platform_school_directory(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get('/platform/school-directory')
            ->assertForbidden();
    }

    public function test_platform_admin_can_provision_a_school_from_the_directory(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $administrator = User::factory()->create(['name' => 'Demo School Admin']);

        $this->actingAs($admin);

        Livewire::test(SchoolDirectory::class)
            ->set('schoolName', 'Provisioned Demo School')
            ->set('schoolSlug', 'provisioned-demo-school')
            ->set('schoolType', 'mixed')
            ->set('timezone', 'Africa/Nairobi')
            ->set('administratorId', $administrator->id)
            ->call('provisionSchool')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('schools', [
            'name' => 'Provisioned Demo School',
            'slug' => 'provisioned-demo-school',
        ]);
        $this->assertDatabaseHas('school_memberships', [
            'user_id' => $administrator->id,
            'status' => 'active',
        ]);
    }
}
