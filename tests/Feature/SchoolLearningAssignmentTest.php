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
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLessonVersion;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\PublishSchoolLearningAssignment;
use App\Services\Schools\SaveSchoolLearningAssignmentDraft;
use Database\Seeders\DemoSchoolSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolLearningAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seed_includes_a_ready_learner_assignment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $this->seed(DemoSchoolSeeder::class);

        $learner = User::query()->where('learner_login_id', 'DEMOLEARN001')->firstOrFail();
        $this->withSession(['_token' => 'demo-login-test-token'])->post(route('learner.login'), [
            '_token' => 'demo-login-test-token',
            'learner_login_id' => $learner->learner_login_id,
            'password' => env('NOVA_DEMO_PASSWORD', 'ChangeMe123!'),
        ])->assertRedirectToRoute('learner.dashboard');
        $this->assertAuthenticatedAs($learner);
        $this->get(route('learner.assignments.index'))
            ->assertOk()
            ->assertSee('Demo fractions practice');
    }

    public function test_demo_refresh_repairs_the_legacy_login_id_without_replacing_the_learner(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $this->seed(DemoSchoolSeeder::class);
        $learner = User::query()->where('learner_login_id', 'DEMOLEARN001')->firstOrFail();
        $learner->update(['learner_login_id' => 'DEMO-LEARNER-001']);
        $this->seed(DemoSchoolSeeder::class);

        $this->assertSame('DEMOLEARN001', $learner->fresh()->learner_login_id);
        $this->assertSame(1, User::query()->where('account_type', 'managed_learner')->count());
        $this->assertDatabaseCount('school_learning_assignment_recipients', 1);
    }

    public function test_assigned_teacher_publishes_assignment_to_a_frozen_class_roster(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $learner = $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);

        $component = Livewire::test(SchoolLearning::class)
            ->set('courseId', $context['course']->id)
            ->set('assignmentLessonVersionId', $context['lessonVersion']->id)
            ->set('assignmentTitle', 'Fractions practice')
            ->set('assignmentInstructions', 'Show how you compare the fractions.')
            ->set('assignmentDueAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveAssignmentDraft')
            ->assertHasNoErrors();

        $assignmentId = $component->get('learningAssignmentId');
        $component->call('publishLearningAssignment', $assignmentId)->assertHasNoErrors();
        $component->call('publishLearningAssignment', $assignmentId)->assertHasNoErrors();

        $this->assertDatabaseHas('school_learning_assignments', [
            'id' => $assignmentId,
            'school_id' => $context['school']->id,
            'source_lesson_version_id' => $context['lessonVersion']->id,
            'title' => 'Fractions practice',
            'status' => 'published',
        ]);
        $this->assertDatabaseCount('school_learning_assignment_recipients', 1);
        $this->assertDatabaseHas('school_learning_assignment_recipients', [
            'school_learning_assignment_id' => $assignmentId,
            'learner_profile_id' => $learner['profile']->id,
            'enrolment_id' => $learner['enrolment']->id,
            'learner_class_membership_id' => $learner['placement']->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $context['school']->id,
            'event_type' => 'school_learning_assignment.published',
            'auditable_id' => $assignmentId,
        ]);

        $laterLearner = $this->learnerInClass($context);
        $this->actingAs($learner['user'])
            ->get(route('learner.assignments.show', $assignmentId))
            ->assertOk()
            ->assertSee('Fractions practice')
            ->assertSee('Show how you compare the fractions.')
            ->assertSee('Compare one half and one quarter.');

        $this->actingAs($laterLearner['user'])
            ->get(route('learner.assignments.index'))
            ->assertOk()
            ->assertDontSee('Fractions practice');
        $this->actingAs($laterLearner['user'])
            ->get(route('learner.assignments.show', $assignmentId))
            ->assertNotFound();

        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);
        Livewire::test(SchoolLearning::class)
            ->set('courseId', $context['course']->id)
            ->call('withdrawVersion', $context['lessonVersion']->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('school_learning_assignments', [
            'id' => $assignmentId,
            'status' => 'withdrawn',
        ]);
        $this->actingAs($learner['user'])
            ->get(route('learner.assignments.show', $assignmentId))
            ->assertNotFound();
    }

    public function test_teacher_without_the_teaching_assignment_cannot_save_its_assignment(): void
    {
        $context = $this->learningContext();
        $otherTeacher = User::factory()->create();
        $membership = $context['school']->memberships()->create([
            'user_id' => $otherTeacher->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $membership->roles()->create(['role' => SchoolRole::Teacher]);
        $this->actingAs($otherTeacher);
        Filament::setTenant($context['school']);

        Livewire::test(SchoolLearning::class)
            ->call('selectCourse', $context['course']->id)->assertForbidden();

        $this->assertDatabaseCount('school_learning_assignments', 0);
    }

    public function test_assignment_draft_rejects_a_cutoff_before_its_due_date(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);

        Livewire::test(SchoolLearning::class)
            ->set('courseId', $context['course']->id)
            ->set('assignmentLessonVersionId', $context['lessonVersion']->id)
            ->set('assignmentTitle', 'Invalid schedule')
            ->set('assignmentInstructions', 'The cutoff is earlier than the due time.')
            ->set('assignmentDueAt', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->set('assignmentCutoffAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveAssignmentDraft')
            ->assertHasErrors(['assignmentCutoffAt']);

        $this->assertDatabaseCount('school_learning_assignments', 0);
    }

    public function test_assignment_draft_cannot_be_published_after_its_due_date(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);

        $component = Livewire::test(SchoolLearning::class)
            ->set('courseId', $context['course']->id)
            ->set('assignmentLessonVersionId', $context['lessonVersion']->id)
            ->set('assignmentTitle', 'Expired draft')
            ->set('assignmentInstructions', 'This draft should not be published late.')
            ->set('assignmentDueAt', now()->addDay()->format('Y-m-d\\TH:i'))
            ->call('saveAssignmentDraft')
            ->assertHasNoErrors();

        $assignmentId = $component->get('learningAssignmentId');
        $this->travel(2)->days();

        $component->call('publishLearningAssignment', $assignmentId)
            ->assertHasErrors(['assignmentDueAt']);

        $this->assertDatabaseHas('school_learning_assignments', [
            'id' => $assignmentId,
            'status' => 'draft',
            'published_at' => null,
        ]);
        $this->assertDatabaseCount('school_learning_assignment_recipients', 0);
    }

    public function test_learner_saves_and_submits_once_with_a_durable_acknowledgement(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $learner = $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        $assignment = $this->publishAssignment($context, 'Explain the fractions', now()->addDays(2));

        $this->actingAs($learner['user'])
            ->post(route('learner.assignments.draft', $assignment), ['response_text' => 'My first attempt.'])
            ->assertRedirect(route('learner.assignments.show', $assignment));

        $this->assertDatabaseHas('school_learning_submissions', [
            'school_learning_assignment_id' => $assignment->id,
            'learner_profile_id' => $learner['profile']->id,
            'response_text' => 'My first attempt.',
            'status' => 'draft',
            'acknowledgement_reference' => null,
        ]);

        $finalResponse = '<script>alert(1)</script>';
        $this->post(route('learner.assignments.submit', $assignment), ['response_text' => $finalResponse])
            ->assertRedirect(route('learner.assignments.show', $assignment));
        $submission = SchoolLearningSubmission::query()->where('school_learning_assignment_id', $assignment->id)->firstOrFail();
        $acknowledgement = $submission->acknowledgement_reference;

        $this->assertSame('submitted', $submission->status);
        $this->assertSame($finalResponse, $submission->response_text);
        $this->assertNotNull($acknowledgement);

        $this->post(route('learner.assignments.submit', $assignment), ['response_text' => 'A changed replay payload.'])
            ->assertRedirect(route('learner.assignments.show', $assignment));
        $this->assertDatabaseCount('school_learning_submissions', 1);
        $this->assertDatabaseHas('school_learning_submissions', [
            'id' => $submission->id,
            'response_text' => $finalResponse,
            'acknowledgement_reference' => $acknowledgement,
        ]);
        $this->assertDatabaseCount('audit_events', 4);

        $this->get(route('learner.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Your response was submitted.')
            ->assertSee($finalResponse)
            ->assertDontSee($finalResponse, false)
            ->assertSee($acknowledgement);

        $this->actingAs($context['teacher']);
        Filament::setTenant($context['school']);
        Livewire::test(SchoolLearning::class)
            ->set('courseId', $context['course']->id)
            ->set('taskView', 'assignments')->assertSee('1 submitted');
    }

    public function test_submission_after_due_is_marked_late_before_cutoff(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $learner = $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        $assignment = $this->publishAssignment($context, 'Late but accepted', now()->addHour(), now()->addDays(1));
        $this->actingAs($learner['user']);
        $this->travel(2)->hours();

        $this->post(route('learner.assignments.submit', $assignment), ['response_text' => 'Submitted inside the late window.'])
            ->assertRedirect(route('learner.assignments.show', $assignment));

        $this->assertDatabaseHas('school_learning_submissions', [
            'school_learning_assignment_id' => $assignment->id,
            'learner_profile_id' => $learner['profile']->id,
            'status' => 'submitted',
            'is_late' => true,
        ]);
    }

    public function test_submission_after_effective_cutoff_is_rejected(): void
    {
        $this->freezeTime();
        $context = $this->learningContext();
        $learner = $this->learnerInClass($context);
        $this->actingAs($context['teacher']);
        $assignment = $this->publishAssignment($context, 'Closed assignment', now()->addHour());
        $this->actingAs($learner['user']);
        $this->travel(2)->hours();

        $this->post(route('learner.assignments.submit', $assignment), ['response_text' => 'Too late.'])
            ->assertSessionHasErrors(['response_text']);

        $this->assertDatabaseCount('school_learning_submissions', 0);
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
