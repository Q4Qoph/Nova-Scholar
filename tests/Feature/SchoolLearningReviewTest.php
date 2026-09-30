<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\School\Pages\SchoolLearning;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
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
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolLearningReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_saves_private_feedback_and_releases_a_zero_score_to_the_learner(): void
    {
        $context = $this->submittedContext();
        $feedback = '<script>alert("feedback")</script> Explain your working.';
        $component = $this->reviewComponent($context)
            ->assertSee('Awaiting review')
            ->call('openSubmissionReview', $context['submission']->id)
            ->assertSee('One half is larger than one quarter.')
            ->set('reviewFeedback', $feedback)
            ->set('reviewScore', '0')->set('reviewMaximumScore', '10')
            ->call('saveSubmissionReview')->assertHasNoErrors()
            ->assertSee('Feedback draft · private');

        $this->actingAs($context['learner']['user'])
            ->get(route('learner.assignments.show', $context['assignment']))
            ->assertOk()->assertSee('Awaiting teacher feedback')->assertDontSee($feedback);
        $this->assertDatabaseHas('school_learning_reviews', [
            'school_learning_submission_id' => $context['submission']->id,
            'feedback' => $feedback, 'score' => 0, 'maximum_score' => 10, 'released_at' => null,
        ]);

        $this->actingAs($context['teacher']);
        $component->call('releaseSubmissionReview')->assertHasNoErrors()->assertSee('Feedback released');
        $component->call('releaseSubmissionReview')->assertHasNoErrors();
        $this->assertDatabaseCount('school_learning_reviews', 1);
        $this->assertSame(1, $context['school']->auditEvents()->where('event_type', 'school_learning_review.released')->count());
        $this->assertSame($context['teacher']->id, $context['submission']->review()->firstOrFail()->released_by_user_id);

        $this->actingAs($context['learner']['user'])
            ->get(route('learner.assignments.show', $context['assignment']))
            ->assertOk()->assertSee('Teacher feedback')->assertSee('Score: 0 / 10')
            ->assertSee($feedback)->assertDontSee($feedback, false)->assertDontSee('Awaiting teacher feedback');
        $this->assertSame('One half is larger than one quarter.', $context['submission']->fresh()->response_text);
    }

    public function test_feedback_without_a_score_can_be_revised_before_release(): void
    {
        $context = $this->submittedContext();
        $component = $this->reviewComponent($context)->call('openSubmissionReview', $context['submission']->id)
            ->set('reviewFeedback', 'First draft')->call('saveSubmissionReview')->assertHasNoErrors()
            ->set('reviewFeedback', 'Good explanation.')->call('saveSubmissionReview')->assertHasNoErrors()
            ->call('releaseSubmissionReview')->assertHasNoErrors();
        $this->assertDatabaseCount('school_learning_reviews', 1);
        $this->assertDatabaseHas('school_learning_reviews', ['feedback' => 'Good explanation.', 'score' => null, 'maximum_score' => null]);
        $this->actingAs($context['learner']['user'])->get(route('learner.assignments.show', $context['assignment']))
            ->assertOk()->assertSee('Good explanation.')->assertSee('Feedback only · no score')->assertDontSee('First draft');
    }

    public function test_unsaved_changes_must_be_saved_before_release_and_released_feedback_is_immutable(): void
    {
        $context = $this->submittedContext();
        $component = $this->reviewComponent($context)->call('openSubmissionReview', $context['submission']->id)
            ->set('reviewFeedback', 'Saved feedback')->call('saveSubmissionReview')->assertHasNoErrors()
            ->set('reviewFeedback', 'Unsaved feedback')->call('releaseSubmissionReview')->assertHasErrors(['reviewFeedback']);
        $this->assertNull($context['submission']->review()->firstOrFail()->released_at);
        $component->call('saveSubmissionReview')->assertHasNoErrors()->call('releaseSubmissionReview')->assertHasNoErrors()
            ->set('reviewFeedback', 'Attempted alteration')->call('saveSubmissionReview')->assertHasErrors(['reviewFeedback']);
        $this->assertSame('Unsaved feedback', $context['submission']->review()->firstOrFail()->feedback);
    }

    #[DataProvider('invalidReviewData')]
    public function test_invalid_feedback_and_scores_are_rejected(array $attributes, string $error): void
    {
        $context = $this->submittedContext();
        $this->reviewComponent($context)->call('openSubmissionReview', $context['submission']->id)
            ->set('reviewFeedback', $attributes['feedback'])->set('reviewScore', $attributes['score'])
            ->set('reviewMaximumScore', $attributes['maximum_score'])->call('saveSubmissionReview')->assertHasErrors([$error]);
        $this->assertDatabaseCount('school_learning_reviews', 0);
    }

    public static function invalidReviewData(): array
    {
        return [
            'blank feedback' => [['feedback' => '   ', 'score' => '', 'maximum_score' => ''], 'reviewFeedback'],
            'long feedback' => [['feedback' => str_repeat('a', 10001), 'score' => '', 'maximum_score' => ''], 'reviewFeedback'],
            'negative score' => [['feedback' => 'Review', 'score' => '-1', 'maximum_score' => '10'], 'reviewScore'],
            'fractional score' => [['feedback' => 'Review', 'score' => '1.5', 'maximum_score' => '10'], 'reviewScore'],
            'above maximum' => [['feedback' => 'Review', 'score' => '11', 'maximum_score' => '10'], 'reviewScore'],
            'zero maximum' => [['feedback' => 'Review', 'score' => '0', 'maximum_score' => '0'], 'reviewMaximumScore'],
            'missing maximum with zero' => [['feedback' => 'Review', 'score' => '0', 'maximum_score' => ''], 'reviewMaximumScore'],
            'missing score' => [['feedback' => 'Review', 'score' => '', 'maximum_score' => '10'], 'reviewScore'],
            'large maximum' => [['feedback' => 'Review', 'score' => '10', 'maximum_score' => '1000001'], 'reviewMaximumScore'],
        ];
    }

    public function test_release_requires_a_saved_review(): void
    {
        $context = $this->submittedContext();
        $this->reviewComponent($context)->call('openSubmissionReview', $context['submission']->id)
            ->call('releaseSubmissionReview')->assertHasErrors(['reviewFeedback']);
        $this->assertDatabaseCount('school_learning_reviews', 0);
    }

    public function test_unassigned_teacher_cannot_open_submitted_responses(): void
    {
        $context = $this->submittedContext();
        $otherTeacher = $this->staff($context['school'], SchoolRole::Teacher);
        $this->actingAs($otherTeacher);
        Filament::setTenant($context['school']);
        Livewire::test(SchoolLearning::class)->set('courseId', $context['course']->id)
            ->call('openAssignmentSubmissions', $context['assignment']->id)->assertNotFound();
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('review', $context['submission']));
    }

    public function test_school_admin_can_review_but_other_school_staff_and_guardians_cannot(): void
    {
        $context = $this->submittedContext();
        $admin = $this->staff($context['school'], SchoolRole::SchoolAdmin);
        $this->actingAs($admin);
        Filament::setTenant($context['school']);
        Livewire::test(SchoolLearning::class)->set('courseId', $context['course']->id)
            ->call('openAssignmentSubmissions', $context['assignment']->id)
            ->call('openSubmissionReview', $context['submission']->id)
            ->set('reviewFeedback', 'Reviewed by school admin')->call('saveSubmissionReview')->assertHasNoErrors();
        $this->assertSame($admin->id, $context['submission']->review()->firstOrFail()->reviewed_by_user_id);
        $foreignSchool = School::factory()->create();
        $foreignTeacher = $this->staff($foreignSchool, SchoolRole::Teacher);
        $guardian = $this->staff($context['school'], SchoolRole::Guardian);
        $bursar = $this->staff($context['school'], SchoolRole::Bursar);
        foreach ([$foreignTeacher, $guardian, $bursar, $context['learner']['user']] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('review', $context['submission']));
        }
        $this->actingAs($foreignTeacher);
        Filament::setTenant($foreignSchool);
        Livewire::test(SchoolLearning::class)->set('courseId', $context['course']->id)
            ->call('openAssignmentSubmissions', $context['assignment']->id)->assertNotFound();
    }

    public function test_revoked_staff_and_withdrawn_assignments_cannot_save_or_release_reviews(): void
    {
        $context = $this->submittedContext();
        $component = $this->reviewComponent($context)->call('openSubmissionReview', $context['submission']->id)
            ->set('reviewFeedback', 'Private')->call('saveSubmissionReview')->assertHasNoErrors();
        $context['school']->memberships()->where('user_id', $context['teacher']->id)->update(['status' => 'inactive']);
        $component->call('releaseSubmissionReview')->assertForbidden();
        $this->assertFalse(Gate::forUser($context['teacher']->fresh())->allows('review', $context['submission']->fresh()));
        $this->assertNull($context['submission']->review()->firstOrFail()->released_at);
        $context['school']->memberships()->where('user_id', $context['teacher']->id)->update(['status' => 'active']);
        $context['assignment']->update(['status' => 'withdrawn']);
        $this->assertFalse(Gate::forUser($context['teacher'])->allows('review', $context['submission']->fresh()));
        $this->actingAs($context['learner']['user'])->get(route('learner.assignments.show', $context['assignment']))->assertNotFound();
    }

    public function test_draft_responses_cannot_be_reviewed_and_other_learners_cannot_read_feedback(): void
    {
        $context = $this->submittedContext();
        $otherLearner = $this->learnerInClass($context);
        app(SaveSchoolLearningReview::class)->handle($context['teacher'], $context['school'], $context['submission'], [
            'feedback' => 'Private to one learner', 'score' => null, 'maximum_score' => null,
        ]);
        app(ReleaseSchoolLearningReview::class)->handle($context['teacher'], $context['school'], $context['submission']);
        $this->actingAs($otherLearner['user'])->get(route('learner.assignments.show', $context['assignment']))->assertNotFound();
        $this->actingAs($context['learner']['user']);
        $context['learner']['enrolment']->update(['status' => 'inactive']);
        $this->get(route('learner.assignments.show', $context['assignment']))->assertNotFound();
        $context['submission']->update(['status' => 'draft']);
        $this->assertFalse(Gate::forUser($context['teacher'])->allows('review', $context['submission']->fresh()));
        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);
        Livewire::test(SchoolLearning::class)->set('courseId', $context['course']->id)
            ->call('openAssignmentSubmissions', $context['assignment']->id)
            ->assertSee('No final responses have been submitted yet.')
            ->call('openSubmissionReview', $context['submission']->id)->assertForbidden();
    }

    public function test_service_uses_explicit_actor_and_rejects_a_foreign_school_submission(): void
    {
        $context = $this->submittedContext();
        $foreignSchool = School::factory()->create();
        $this->expectException(ModelNotFoundException::class);
        app(SaveSchoolLearningReview::class)->handle($context['teacher'], $foreignSchool, $context['submission'], [
            'feedback' => 'Wrong school', 'score' => null, 'maximum_score' => null,
        ]);
    }

    private function reviewComponent(array $context): Testable
    {
        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);

        return Livewire::test(SchoolLearning::class)->set('courseId', $context['course']->id)
            ->call('openAssignmentSubmissions', $context['assignment']->id);
    }

    private function staff(School $school, SchoolRole $role): User
    {
        $user = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => $role]);

        return $user;
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
