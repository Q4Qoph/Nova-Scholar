<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningReview;
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLessonVersion;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\PublishSchoolLearningAssignment;
use App\Services\Schools\ReleaseSchoolLearningReview;
use App\Services\Schools\SaveSchoolLearningAssignmentDraft;
use App\Services\Schools\SaveSchoolLearningReview;
use App\Services\Schools\SubmitSchoolLearningAssignment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SchoolLearningReviewConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'pgsql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent feedback release requires PostgreSQL and pcntl.');
        }
    }

    public function test_concurrent_release_creates_one_release_event_and_preserves_the_saved_feedback(): void
    {
        $context = $this->submittedContext();
        app(SaveSchoolLearningReview::class)->handle($context['teacher'], $context['school'], $context['submission'], [
            'feedback' => 'Saved feedback from the teacher.', 'score' => 0, 'maximum_score' => 10,
        ]);
        $teacherId = $context['teacher']->id;
        $schoolId = $context['school']->id;
        $submissionId = $context['submission']->id;
        DB::purge();
        $parentSockets = [];
        $processIds = [];
        foreach (range(1, 2) as $workerNumber) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if ($sockets === false) {
                throw new RuntimeException('Unable to create feedback-release worker sockets.');
            }
            [$parentSocket, $childSocket] = $sockets;
            $processId = pcntl_fork();
            if ($processId === -1) {
                throw new RuntimeException('Unable to fork a feedback-release worker.');
            }
            if ($processId === 0) {
                fclose($parentSocket);
                foreach ($parentSockets as $inheritedSocket) {
                    fclose($inheritedSocket);
                }
                if (fgets($childSocket) !== "go\n") {
                    fclose($childSocket);
                    exit(2);
                }
                try {
                    DB::reconnect();
                    $review = app(ReleaseSchoolLearningReview::class)->handle(
                        User::findOrFail($teacherId), School::findOrFail($schoolId), SchoolLearningSubmission::findOrFail($submissionId),
                    );
                    fwrite($childSocket, $review->id."\n");
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
        $review = SchoolLearningReview::query()->sole();
        $this->assertSame([(string) $review->id, (string) $review->id], $results);
        $this->assertNotNull($review->released_at);
        $this->assertSame('Saved feedback from the teacher.', $review->feedback);
        $this->assertSame(0, $review->score);
        $this->assertSame(1, $context['school']->auditEvents()->where('event_type', 'school_learning_review.released')->count());
    }

    private function submittedContext(): array
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $learner = $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        $assignment = $this->publishAssignment($context, 'Explain fractions', now()->addDays(2));
        $this->actingAs($learner['user']);
        $submission = app(SubmitSchoolLearningAssignment::class)->handle($learner['user'], $assignment, 'One half is larger than one quarter.');

        return [...$context, 'learner' => $learner, 'assignment' => $assignment, 'submission' => $submission];
    }

    /** @return array{school: School, teacher: User, teachingAssignment: TeachingAssignment, course: SchoolCourse, lessonVersion: SchoolLessonVersion} */
    private function learningContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Africa/Nairobi']);
        $teacher = User::factory()->create();
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
        Term::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'starts_on' => today()->subMonth(),
            'ends_on' => today()->addMonths(3),
            'status' => 'open',
        ]);
        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'status' => 'active',
        ]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $teachingAssignment = TeachingAssignment::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'status' => 'active',
        ]);
        $course = SchoolCourse::factory()->create([
            'school_id' => $school->id,
            'teaching_assignment_id' => $teachingAssignment->id,
            'created_by_user_id' => $teacher->id,
        ]);
        $lesson = $course->lessons()->create(['position' => 1]);
        $lessonVersion = $lesson->versions()->create([
            'version_number' => 1,
            'title' => 'Fractions',
            'body' => 'Compare one half and one quarter.',
            'status' => 'published',
            'created_by_user_id' => $teacher->id,
            'published_at' => now(),
        ]);

        return compact('school', 'teacher', 'teachingAssignment', 'course', 'lessonVersion');
    }

    /** @param array{school: School, teacher: User, teachingAssignment: TeachingAssignment, course: SchoolCourse, lessonVersion: SchoolLessonVersion} $context */
    private function publishAssignment(array $context, string $title, Carbon $dueAt, ?Carbon $cutoffAt = null): SchoolLearningAssignment
    {
        $school = $context['school'];
        $draft = app(SaveSchoolLearningAssignmentDraft::class)->handle(
            $context['teacher'],
            $school,
            $context['teachingAssignment'],
            $context['course'],
            $context['lessonVersion']->id,
            $title,
            'Explain the answer in your own words.',
            $dueAt->timezone($school->timezone)->format('Y-m-d\\TH:i'),
            $cutoffAt?->timezone($school->timezone)->format('Y-m-d\\TH:i'),
        );

        return app(PublishSchoolLearningAssignment::class)->handle($context['teacher'], $school, $draft);
    }

    /** @param array{school: School, teachingAssignment: TeachingAssignment} $context
     * @return array{user: User, profile: LearnerProfile, enrolment: Enrolment, placement: LearnerClassMembership}
     */
    private function learnerInClass(array $context): array
    {
        $user = User::factory()->create([
            'account_type' => 'managed_learner',
            'learner_login_id' => 'learner-'.Str::lower(Str::random(12)),
            'learner_activated_at' => now(),
            'learner_deactivated_at' => null,
        ]);
        $profile = LearnerProfile::factory()->create(['user_id' => $user->id]);
        $enrolment = Enrolment::factory()->create([
            'school_id' => $context['school']->id,
            'learner_profile_id' => $profile->id,
            'status' => 'active',
        ]);
        $placement = LearnerClassMembership::factory()->create([
            'school_id' => $context['school']->id,
            'enrolment_id' => $enrolment->id,
            'class_group_id' => $context['teachingAssignment']->class_group_id,
            'starts_on' => today()->subMonth(),
            'status' => 'active',
        ]);

        return compact('user', 'profile', 'enrolment', 'placement');
    }
}
