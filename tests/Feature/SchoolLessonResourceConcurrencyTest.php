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
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\StoreSchoolLessonResource;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolLessonResourceConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent resource quota coverage requires PostgreSQL and pcntl.');
        }
    }

    public function test_concurrent_uploads_cannot_overbook_the_school_resource_limit(): void
    {
        Storage::fake('local');
        Queue::fake([ScanSchoolLessonResourceJob::class]);
        [$teacher, $school, $version] = $this->draftContext();

        SchoolLessonResource::factory()->count(499)->create([
            'school_id' => $school->id,
            'school_lesson_version_id' => $version->id,
            'uploaded_by_user_id' => $teacher->id,
            'bytes_purged_at' => null,
        ]);

        $schoolId = $school->id;
        $versionId = $version->id;
        $teacherId = $teacher->id;
        DB::purge();

        $parentSockets = [];
        $processIds = [];

        foreach (['quota-race-first.pdf', 'quota-race-second.pdf'] as $fileName) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Unable to create resource-upload worker sockets.');
            }

            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new RuntimeException('Unable to fork a resource-upload worker.');
            }

            if ($processId === 0) {
                fclose($parentSocket);

                foreach ($parentSockets as $inheritedParentSocket) {
                    fclose($inheritedParentSocket);
                }

                if (fgets($childSocket) !== "go\n") {
                    fclose($childSocket);
                    exit(2);
                }

                try {
                    DB::reconnect();
                    $worker = User::query()->findOrFail($teacherId);
                    Auth::login($worker);
                    $workerSchool = School::query()->findOrFail($schoolId);
                    $workerVersion = SchoolLessonVersion::query()->findOrFail($versionId);
                    $upload = UploadedFile::fake()->createWithContent($fileName, "%PDF-1.4\nConcurrent lesson resource\n%%EOF");

                    app(StoreSchoolLessonResource::class)->handle(
                        $worker,
                        $workerSchool,
                        $workerVersion,
                        $upload,
                        SchoolLessonResourceRightsBasis::EducatorCreated,
                        null,
                    );
                    fwrite($childSocket, "stored\n");
                } catch (ValidationException) {
                    fwrite($childSocket, "quota_blocked\n");
                } catch (Throwable $exception) {
                    fwrite($childSocket, 'failed:'.$exception::class."\n");
                }

                fclose($childSocket);
                exit(0);
            }

            fclose($childSocket);
            stream_set_timeout($parentSocket, 60);
            $parentSockets[] = $parentSocket;
            $processIds[] = $processId;
        }

        foreach ($parentSockets as $parentSocket) {
            fwrite($parentSocket, "go\n");
        }

        $results = [];

        foreach ($parentSockets as $parentSocket) {
            $results[] = trim((string) fgets($parentSocket));
            fclose($parentSocket);
        }

        foreach ($processIds as $processId) {
            pcntl_waitpid($processId, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }

        DB::reconnect();
        sort($results);

        $this->assertSame(['quota_blocked', 'stored'], $results);
        $this->assertSame(500, SchoolLessonResource::query()->where('school_id', $schoolId)->whereNull('bytes_purged_at')->count());
        $this->assertSame(1, SchoolLessonResource::query()->where('school_id', $schoolId)->where('byte_size', '<', 1024)->count());
        $this->assertSame(1, DB::table('audit_events')->where('school_id', $schoolId)->where('event_type', 'lesson_resource.quarantined')->count());
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
}
