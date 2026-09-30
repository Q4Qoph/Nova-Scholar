<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLesson;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerSchoolCourseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_shows_published_versions_and_clean_resources_but_hides_drafts(): void
    {
        $this->withoutVite();
        Storage::fake('local');
        [$learner, $school, $course] = $this->learnerCourseContext();
        $lesson = SchoolLesson::factory()->create(['school_course_id' => $course->id, 'position' => 1]);
        $published = SchoolLessonVersion::factory()->create([
            'school_lesson_id' => $lesson->id,
            'version_number' => 1,
            'title' => 'Published fractions lesson',
            'body' => 'Published lesson body.',
            'status' => SchoolLessonVersionStatus::Published,
            'created_by_user_id' => $course->created_by_user_id,
            'published_at' => now(),
        ]);
        SchoolLessonVersion::factory()->create([
            'school_lesson_id' => $lesson->id,
            'version_number' => 2,
            'title' => 'Private draft title',
            'body' => 'Private draft body.',
            'status' => SchoolLessonVersionStatus::Draft,
            'created_by_user_id' => $course->created_by_user_id,
        ]);
        $cleanResource = $this->resource($school, $published, SchoolLessonResourceStatus::Clean, 'clean-worksheet.pdf');
        $pendingResource = $this->resource($school, $published, SchoolLessonResourceStatus::ScanPending, 'pending-worksheet.pdf');

        Storage::disk('local')->put($cleanResource->storage_key, '%PDF-1.4'."\n".'%%EOF');
        Storage::disk('local')->put($pendingResource->storage_key, '%PDF-1.4'."\n".'%%EOF');

        $this->actingAs($learner)
            ->get(route('learner.courses.show', $course))
            ->assertOk()
            ->assertSee('Published fractions lesson')
            ->assertSee('Published lesson body.')
            ->assertSee('clean-worksheet.pdf')
            ->assertDontSee('Private draft title')
            ->assertDontSee('Private draft body.')
            ->assertDontSee('pending-worksheet.pdf');
    }

    public function test_course_index_omits_courses_without_a_published_lesson(): void
    {
        $this->withoutVite();
        [$learner, , $course] = $this->learnerCourseContext();

        $this->actingAs($learner)
            ->get(route('learner.courses.index'))
            ->assertOk()
            ->assertDontSee($course->title)
            ->assertSee('No published lessons are available');
    }

    public function test_learner_without_a_current_placement_gets_not_found_for_the_course(): void
    {
        $this->withoutVite();
        [$learner, , $course, $placement] = $this->learnerCourseContext();
        $placement->forceFill(['ends_on' => today()->subDay()])->save();

        $this->actingAs($learner)
            ->get(route('learner.courses.show', $course))
            ->assertNotFound();
    }

    public function test_learner_downloads_a_clean_resource_for_a_published_lesson(): void
    {
        Storage::fake('local');
        [$learner, $school, $course] = $this->learnerCourseContext();
        $version = $this->publishedVersion($course);
        $resource = $this->resource($school, $version, SchoolLessonResourceStatus::Clean, 'worksheet.pdf');
        Storage::disk('local')->put($resource->storage_key, '%PDF-1.4'."\n".'Private lesson file'."\n".'%%EOF');

        $this->actingAs($learner)
            ->get(route('learner.lesson-resources.download', $resource))
            ->assertDownload('worksheet.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertStreamedContent('%PDF-1.4'."\n".'Private lesson file'."\n".'%%EOF');
    }

    public function test_learner_cannot_download_a_clean_resource_from_a_withdrawn_version(): void
    {
        Storage::fake('local');
        [$learner, $school, $course] = $this->learnerCourseContext();
        $version = $this->publishedVersion($course);
        $version->forceFill([
            'status' => SchoolLessonVersionStatus::Withdrawn,
            'withdrawn_at' => now(),
        ])->save();
        $resource = $this->resource($school, $version, SchoolLessonResourceStatus::Clean, 'withdrawn.pdf');
        Storage::disk('local')->put($resource->storage_key, '%PDF-1.4'."\n".'%%EOF');

        $this->actingAs($learner)
            ->get(route('learner.lesson-resources.download', $resource))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: School, 2: SchoolCourse, 3: LearnerClassMembership} */
    private function learnerCourseContext(): array
    {
        $school = School::factory()->create();
        $learner = User::factory()->create([
            'account_type' => 'managed_learner',
            'learner_activated_at' => now(),
            'learner_deactivated_at' => null,
        ]);
        $profile = LearnerProfile::factory()->create(['user_id' => $learner->id, 'status' => 'active']);
        $enrolment = Enrolment::factory()->create([
            'school_id' => $school->id,
            'learner_profile_id' => $profile->id,
            'status' => 'active',
        ]);
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
        $placement = LearnerClassMembership::factory()->create([
            'school_id' => $school->id,
            'enrolment_id' => $enrolment->id,
            'class_group_id' => $classGroup->id,
            'starts_on' => today()->subDay(),
            'ends_on' => null,
            'status' => 'active',
        ]);
        $teacher = User::factory()->create();
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
            'title' => 'Current mathematics lessons',
        ]);

        return [$learner, $school, $course, $placement];
    }

    private function publishedVersion(SchoolCourse $course): SchoolLessonVersion
    {
        $lesson = SchoolLesson::factory()->create(['school_course_id' => $course->id, 'position' => 1]);

        return SchoolLessonVersion::factory()->create([
            'school_lesson_id' => $lesson->id,
            'version_number' => 1,
            'status' => SchoolLessonVersionStatus::Published,
            'created_by_user_id' => $course->created_by_user_id,
            'published_at' => now(),
        ]);
    }

    private function resource(
        School $school,
        SchoolLessonVersion $version,
        SchoolLessonResourceStatus $status,
        string $displayName,
    ): SchoolLessonResource {
        return SchoolLessonResource::factory()->create([
            'school_id' => $school->id,
            'school_lesson_version_id' => $version->id,
            'uploaded_by_user_id' => $version->created_by_user_id,
            'display_name' => $displayName,
            'storage_key' => 'school-lesson-resources/'.$displayName,
            'status' => $status,
            'media_type' => 'application/pdf',
        ]);
    }
}
