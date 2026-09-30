<?php

namespace Tests\Feature;

use App\Jobs\ScanSchoolLessonResourceJob;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLesson;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceRightsBasis;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\CreateSchoolCourse;
use App\Services\Schools\PublishSchoolLessonVersion;
use App\Services\Schools\SaveSchoolLessonDraft;
use App\Services\Schools\SchoolLessonResourceScanner;
use App\Services\Schools\SchoolLessonResourceScanResult;
use App\Services\Schools\SchoolLessonResourceScanVerdict;
use App\Services\Schools\StoreSchoolLessonResource;
use App\Services\Schools\WithdrawSchoolLessonVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class SchoolLessonResourceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_stores_a_valid_upload_privately_and_queues_scanning(): void
    {
        Storage::fake('local');
        Queue::fake([ScanSchoolLessonResourceJob::class]);
        [$teacher, $school, $version] = $this->draftContext();
        $upload = UploadedFile::fake()->createWithContent('worksheet.pdf', "%PDF-1.4\nLesson resource\n%%EOF");

        $resource = app(StoreSchoolLessonResource::class)->handle(
            $teacher,
            $school,
            $version,
            $upload,
            SchoolLessonResourceRightsBasis::EducatorCreated,
            null,
        );

        $this->assertSame(SchoolLessonResourceStatus::ScanPending, $resource->status);
        $this->assertSame($school->id, $resource->school_id);
        $this->assertSame($teacher->id, $resource->uploaded_by_user_id);
        $this->assertNotSame('worksheet.pdf', $resource->storage_key);
        Storage::disk('local')->assertExists($resource->storage_key);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_resource.quarantined',
            'auditable_id' => $resource->id,
        ]);
        Queue::assertPushed(ScanSchoolLessonResourceJob::class, fn (ScanSchoolLessonResourceJob $job): bool => $job->resourceId === $resource->id);
    }

    public function test_assigned_teacher_creates_one_course_and_new_drafts_preserve_published_content(): void
    {
        [$teacher, $school, $existingVersion] = $this->draftContext();
        $existingAssignment = $existingVersion->lesson->course->teachingAssignment;
        $anotherTeacher = User::factory()->create();
        $membership = $school->memberships()->create([
            'user_id' => $anotherTeacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $existingAssignment->class_group_id,
            'subject_id' => $existingAssignment->subject_id,
            'teacher_user_id' => $anotherTeacher->id,
            'status' => 'active',
        ]);
        $this->actingAs($anotherTeacher);

        $course = app(CreateSchoolCourse::class)->handle($anotherTeacher, $school, $assignment, '  Science  ');
        $repeatedCourse = app(CreateSchoolCourse::class)->handle($anotherTeacher, $school, $assignment, 'Ignored second title');

        $this->assertSame($course->id, $repeatedCourse->id);
        $this->assertSame('Science', $course->title);
        $this->assertDatabaseCount('school_courses', 2);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_course.created',
            'auditable_id' => $course->id,
        ]);

        $draft = app(SaveSchoolLessonDraft::class)->handle($anotherTeacher, $school, $course, 'Draft title', 'First draft body.');
        $editedDraft = app(SaveSchoolLessonDraft::class)->handle($anotherTeacher, $school, $course, '  Published title  ', 'Published lesson body.', $draft->school_lesson_id);
        $published = app(PublishSchoolLessonVersion::class)->handle($anotherTeacher, $school, $editedDraft);
        $nextDraft = app(SaveSchoolLessonDraft::class)->handle($anotherTeacher, $school, $course, 'Next version', 'Changed lesson body.', $draft->school_lesson_id);

        $this->assertSame($draft->id, $editedDraft->id);
        $this->assertSame(SchoolLessonVersionStatus::Published, $published->status);
        $this->assertSame('Published title', $published->title);
        $this->assertSame('Published lesson body.', $published->body);
        $this->assertSame(2, $nextDraft->version_number);
        $this->assertSame(SchoolLessonVersionStatus::Draft, $nextDraft->status);
        $this->assertSame('Published lesson body.', $published->fresh()->body);
    }

    public function test_malformed_pdf_is_rejected_before_storage_or_quota_reservation(): void
    {
        Storage::fake('local');
        Queue::fake([ScanSchoolLessonResourceJob::class]);
        [$teacher, $school, $version] = $this->draftContext();
        $upload = UploadedFile::fake()->createWithContent('broken.pdf', 'not a PDF document');

        try {
            app(StoreSchoolLessonResource::class)->handle(
                $teacher,
                $school,
                $version,
                $upload,
                SchoolLessonResourceRightsBasis::EducatorCreated,
                null,
            );
            $this->fail('Malformed PDF input should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('PDF file is malformed', $exception->errors()['upload'][0]);
        }

        $this->assertDatabaseCount('school_lesson_resources', 0);
        Storage::disk('local')->assertDirectoryEmpty('school-lesson-resources');
        Queue::assertNothingPushed();
    }

    public function test_licensed_upload_requires_a_source_reference(): void
    {
        [$teacher, $school, $version] = $this->draftContext();
        $upload = UploadedFile::fake()->createWithContent('worksheet.pdf', "%PDF-1.4\n%%EOF");

        try {
            app(StoreSchoolLessonResource::class)->handle(
                $teacher,
                $school,
                $version,
                $upload,
                SchoolLessonResourceRightsBasis::Licensed,
                null,
            );
            $this->fail('A licensed upload should require its source reference.');
        } catch (ValidationException $exception) {
            $this->assertSame('Add the licence or permission source.', $exception->errors()['rightsReference'][0]);
        }

        $this->assertDatabaseCount('school_lesson_resources', 0);
    }

    public function test_school_storage_limit_rejects_another_upload_without_persisting_it(): void
    {
        Storage::fake('local');
        Queue::fake([ScanSchoolLessonResourceJob::class]);
        [$teacher, $school, $version] = $this->draftContext();
        SchoolLessonResource::factory()->create([
            'school_id' => $school->id,
            'school_lesson_version_id' => $version->id,
            'uploaded_by_user_id' => $teacher->id,
            'byte_size' => 2 * 1024 * 1024 * 1024,
            'status' => SchoolLessonResourceStatus::Rejected,
            'bytes_purged_at' => null,
        ]);
        $upload = UploadedFile::fake()->createWithContent('worksheet.pdf', "%PDF-1.4\n%%EOF");

        try {
            app(StoreSchoolLessonResource::class)->handle(
                $teacher,
                $school,
                $version,
                $upload,
                SchoolLessonResourceRightsBasis::EducatorCreated,
                null,
            );
            $this->fail('An upload should not exceed the school storage limit.');
        } catch (ValidationException $exception) {
            $this->assertSame('This school has reached its private resource storage limit.', $exception->errors()['upload'][0]);
        }

        $this->assertDatabaseCount('school_lesson_resources', 1);
        Storage::disk('local')->assertDirectoryEmpty('school-lesson-resources');
        Queue::assertNothingPushed();
    }

    public function test_scanner_outage_keeps_resource_pending_and_blocks_publication(): void
    {
        Storage::fake('local');
        [$teacher, $school, $version, $resource] = $this->pendingResource();
        $scanner = $this->scannerWith(SchoolLessonResourceScanVerdict::Unavailable);

        try {
            (new ScanSchoolLessonResourceJob($resource->id))->handle($scanner);
            $this->fail('An unavailable scanner should not mark the resource clean.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The configured malware scanner is unavailable.', $exception->getMessage());
        }

        $this->assertSame(SchoolLessonResourceStatus::ScanPending, $resource->fresh()->status);
        $this->assertSame('scanner_unavailable', $resource->fresh()->scan_result_code);

        try {
            app(PublishSchoolLessonVersion::class)->handle($teacher, $school, $version);
            $this->fail('A lesson with an unscanned resource should not publish.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Wait until every lesson resource', $exception->errors()['lesson'][0]);
        }

        $this->assertSame(SchoolLessonVersionStatus::Draft, $version->fresh()->status);
    }

    public function test_clean_scan_allows_publication_and_records_scan_metadata(): void
    {
        Storage::fake('local');
        [$teacher, $school, $version, $resource] = $this->pendingResource();
        $job = new ScanSchoolLessonResourceJob($resource->id);
        $job->handle($this->scannerWith(SchoolLessonResourceScanVerdict::Clean, 'test-clamav', 'sig-2026-09-28'));

        $this->assertSame(SchoolLessonResourceStatus::Clean, $resource->fresh()->status);
        $this->assertSame('test-clamav', $resource->fresh()->scanner_name);
        $this->assertSame('sig-2026-09-28', $resource->fresh()->scanner_signature_version);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_resource.scan_clean',
            'auditable_id' => $resource->id,
        ]);

        $published = app(PublishSchoolLessonVersion::class)->handle($teacher, $school, $version);

        $this->assertSame(SchoolLessonVersionStatus::Published, $published->status);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_version.published',
            'auditable_id' => $version->id,
        ]);
    }

    public function test_infected_upload_is_rejected_and_its_private_bytes_are_removed(): void
    {
        Storage::fake('local');
        [, , , $resource] = $this->pendingResource();
        $storageKey = $resource->storage_key;
        Storage::disk('local')->put($storageKey, 'malware sample');

        (new ScanSchoolLessonResourceJob($resource->id))
            ->handle($this->scannerWith(SchoolLessonResourceScanVerdict::Infected, 'test-clamav', 'sig-2026-09-28'));

        $resource->refresh();
        $this->assertSame(SchoolLessonResourceStatus::Rejected, $resource->status);
        $this->assertSame('infected', $resource->scan_result_code);
        $this->assertNotNull($resource->bytes_purged_at);
        $this->assertNull($resource->storage_key);
        Storage::disk('local')->assertMissing($storageKey);
    }

    public function test_withdrawal_starts_ninety_day_retention_then_purge_keeps_metadata(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        [$teacher, $school, $version, $resource] = $this->pendingResource();
        $storageKey = $resource->storage_key;
        (new ScanSchoolLessonResourceJob($resource->id))
            ->handle($this->scannerWith(SchoolLessonResourceScanVerdict::Clean));
        $published = app(PublishSchoolLessonVersion::class)->handle($teacher, $school, $version);

        app(WithdrawSchoolLessonVersion::class)->handle($teacher, $school, $published);

        $resource->refresh();
        $this->assertSame(now()->addDays(90)->timestamp, $resource->purge_after->timestamp);
        $this->assertSame(SchoolLessonResourceStatus::Clean, $resource->status);
        Storage::disk('local')->assertExists($storageKey);

        $this->travelTo(now()->addDays(91));
        Artisan::call('school:purge-lesson-resources');

        $resource->refresh();
        $this->assertSame(SchoolLessonResourceStatus::Purged, $resource->status);
        $this->assertNull($resource->storage_key);
        $this->assertNotNull($resource->bytes_purged_at);
        $this->assertSame(1024, $resource->byte_size);
        Storage::disk('local')->assertMissing($storageKey);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_resource.bytes_purged',
            'auditable_id' => $resource->id,
        ]);
    }

    /** @return array{0: User, 1: School, 2: SchoolLessonVersion} */
    private function draftContext(): array
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        $membership = $school->memberships()->create([
            'user_id' => $teacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'starts_on' => today()->subMonth(),
            'ends_on' => today()->addMonths(10),
            'status' => 'open',
        ]);
        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'status' => 'active',
        ]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'status' => 'active',
        ]);
        $course = SchoolCourse::factory()->create([
            'school_id' => $school->id,
            'teaching_assignment_id' => $assignment->id,
            'created_by_user_id' => $teacher->id,
        ]);
        $lesson = SchoolLesson::factory()->create(['school_course_id' => $course->id, 'position' => 1]);
        $version = SchoolLessonVersion::factory()->create([
            'school_lesson_id' => $lesson->id,
            'version_number' => 1,
            'status' => SchoolLessonVersionStatus::Draft,
            'created_by_user_id' => $teacher->id,
        ]);

        return [$teacher, $school, $version];
    }

    /** @return array{0: User, 1: School, 2: SchoolLessonVersion, 3: SchoolLessonResource} */
    private function pendingResource(): array
    {
        [$teacher, $school, $version] = $this->draftContext();
        $resource = SchoolLessonResource::factory()->create([
            'school_id' => $school->id,
            'school_lesson_version_id' => $version->id,
            'uploaded_by_user_id' => $teacher->id,
            'storage_disk' => 'local',
            'storage_key' => 'school-lesson-resources/quarantine/'.$version->id.'.pdf',
            'status' => SchoolLessonResourceStatus::ScanPending,
        ]);
        Storage::disk('local')->put($resource->storage_key, "%PDF-1.4\nTest resource\n%%EOF");

        return [$teacher, $school, $version, $resource];
    }

    private function scannerWith(
        SchoolLessonResourceScanVerdict $verdict,
        string $scannerName = 'test-clamav',
        ?string $signatureVersion = null,
    ): SchoolLessonResourceScanner {
        return new class($verdict, $scannerName, $signatureVersion) implements SchoolLessonResourceScanner
        {
            public function __construct(
                private readonly SchoolLessonResourceScanVerdict $verdict,
                private readonly string $scannerName,
                private readonly ?string $signatureVersion,
            ) {}

            public function scan(string $absolutePath): SchoolLessonResourceScanResult
            {
                return new SchoolLessonResourceScanResult($this->verdict, $this->scannerName, $this->signatureVersion);
            }
        };
    }
}
