<?php

namespace Tests\Feature;

use App\Models\Enrolment;
use App\Models\ImportBatch;
use App\Models\School;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LearnerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_stage_a_csv_and_review_row_validation(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $file = $this->csvFile("first_name,last_name,preferred_name,date_of_birth,admission_number\nAmani,Otieno,Ami,2012-05-14,IMP-001\nMissing,Date,,not-a-date,IMP-002\n");

        $response = $this->actingAs($admin)->post(route('schools.learner-imports.store', $school), ['file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();

        $response->assertRedirectToRoute('schools.learner-imports.show', [$school, $batch]);
        $this->assertDatabaseHas('import_batches', ['school_id' => $school->id, 'total_rows' => 2, 'valid_rows' => 1, 'invalid_rows' => 1]);
        $this->assertDatabaseHas('import_rows', ['import_batch_id' => $batch->id, 'row_number' => 2, 'status' => 'valid']);
        $this->assertDatabaseHas('import_rows', ['import_batch_id' => $batch->id, 'row_number' => 3, 'status' => 'invalid']);

        $this->actingAs($admin)
            ->get(route('schools.learner-imports.show', [$school, $batch]))
            ->assertOk()
            ->assertSee('Review learner import')
            ->assertSee('must be a valid date');
    }

    public function test_admin_can_commit_valid_rows_while_invalid_rows_remain_uncommitted(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $batch = $this->stage($admin, $school, "first_name,last_name,preferred_name,date_of_birth,admission_number\nAmani,Otieno,,2012-05-14,IMP-003\nBroken,Row,,bad-date,IMP-004\n");

        $this->actingAs($admin)
            ->post(route('schools.learner-imports.commit', [$school, $batch]))
            ->assertRedirectToRoute('schools.learner-imports.show', [$school, $batch]);

        $this->assertDatabaseHas('enrolments', ['school_id' => $school->id, 'admission_number' => 'IMP-003']);
        $this->assertDatabaseMissing('enrolments', ['school_id' => $school->id, 'admission_number' => 'IMP-004']);
        $this->assertDatabaseHas('import_rows', ['import_batch_id' => $batch->id, 'row_number' => 2, 'status' => 'committed']);
        $this->assertDatabaseHas('import_rows', ['import_batch_id' => $batch->id, 'row_number' => 3, 'status' => 'invalid']);
        $this->assertDatabaseHas('audit_events', ['school_id' => $school->id, 'event_type' => 'learner_import.committed']);
    }

    public function test_replaying_a_csv_and_commit_does_not_duplicate_learners(): void
    {
        [$admin, $school] = $this->schoolAdmin();
        $csv = "first_name,last_name,preferred_name,date_of_birth,admission_number\nNia,Kamau,,2011-01-01,IMP-005\n";
        $batch = $this->stage($admin, $school, $csv);

        $this->actingAs($admin)
            ->post(route('schools.learner-imports.store', $school), ['file' => $this->csvFile($csv)])
            ->assertRedirectToRoute('schools.learner-imports.show', [$school, $batch]);

        $this->actingAs($admin)->post(route('schools.learner-imports.commit', [$school, $batch]))->assertRedirect();
        $this->actingAs($admin)->post(route('schools.learner-imports.commit', [$school, $batch]))->assertRedirect();

        $this->assertSame(1, Enrolment::query()->where('school_id', $school->id)->where('admission_number', 'IMP-005')->count());
        $this->assertSame(1, ImportBatch::query()->where('school_id', $school->id)->count());
    }

    public function test_only_school_admin_can_stage_and_commit_an_import(): void
    {
        [$teacher, $school] = $this->schoolContext(SchoolRole::Teacher);
        $file = $this->csvFile("first_name,last_name,preferred_name,date_of_birth,admission_number\nAmani,Otieno,,2012-05-14,IMP-006\n");

        $this->actingAs($teacher)
            ->post(route('schools.learner-imports.store', $school), ['file' => $file])
            ->assertForbidden();

        $batch = ImportBatch::factory()->for($school)->for($teacher, 'uploadedBy')->create();
        $this->actingAs($teacher)
            ->post(route('schools.learner-imports.commit', [$school, $batch]))
            ->assertForbidden();
    }

    public function test_invalid_headers_are_rejected_and_cross_school_batches_are_hidden(): void
    {
        [$admin, $school] = $this->schoolAdmin();

        $this->actingAs($admin)
            ->post(route('schools.learner-imports.store', $school), ['file' => $this->csvFile("name,admission_number\nAmani,IMP-007\n")])
            ->assertSessionHasErrors('file');

        $otherSchool = School::factory()->create();
        $otherBatch = ImportBatch::factory()->for($otherSchool)->create();

        $this->actingAs($admin)
            ->get(route('schools.learner-imports.show', [$school, $otherBatch]))
            ->assertNotFound();
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

    private function stage(User $admin, School $school, string $csv): ImportBatch
    {
        $this->actingAs($admin)->post(route('schools.learner-imports.store', $school), ['file' => $this->csvFile($csv)])->assertRedirect();

        return ImportBatch::query()->where('school_id', $school->id)->firstOrFail();
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('learners.csv', $contents);
    }
}
