<?php

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
use App\Models\SchoolLearningAssignmentRecipient;
use App\Models\SchoolLearningReview;
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLessonVersion;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\BuildLearnerTaskSummary;
use App\Services\Schools\BuildSchoolSetupChecklist;
use App\Services\Schools\BuildSchoolTaskSummary;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolTrialUsabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_summaries_keep_private_feedback_hidden_and_recompute_after_release_and_revocation(): void
    {
        $c = $this->context();
        $submission = $this->submission($c);
        $review = SchoolLearningReview::factory()->create(['school_learning_submission_id' => $submission->id, 'feedback' => 'Private words']);
        $schoolTasks = app(BuildSchoolTaskSummary::class);
        $learnerTasks = app(BuildLearnerTaskSummary::class);
        $this->assertSame(1, $schoolTasks->handle($c['teacher'], $c['school'])['pending']);
        $this->assertSame(['needed' => 0, 'closed' => 0, 'feedback' => 0], $learnerTasks->handle($c['learner']));
        $this->actingAs($c['learner'])->get(route('learner.assignments.index'))->assertOk()->assertDontSee('Private words')->assertDontSee('Feedback available');
        $review->update(['released_at' => now(), 'score' => 0, 'maximum_score' => 10]);
        $this->assertSame(0, $schoolTasks->handle($c['teacher'], $c['school'])['pending']);
        $this->assertSame(1, $learnerTasks->handle($c['learner'])['feedback']);
        $this->get(route('learner.assignments.index'))->assertSee('Feedback available')->assertDontSee('Private words');
        $c['enrolment']->update(['status' => 'inactive']);
        $this->assertSame(0, $learnerTasks->handle($c['learner'])['feedback']);
        $c['school']->memberships()->where('user_id', $c['teacher']->id)->update(['status' => 'inactive']);
        $this->assertSame(0, $schoolTasks->handle($c['teacher'], $c['school'])['pending']);
    }

    public function test_task_summaries_distinguish_closed_work_and_other_teachers_or_schools(): void
    {
        $c = $this->context();
        $tasks = app(BuildLearnerTaskSummary::class);
        $this->assertSame(1, $tasks->handle($c['learner'])['needed']);
        $c['assignment']->update(['due_at' => now()->subDay()]);
        $this->assertSame(['needed' => 0, 'closed' => 1, 'feedback' => 0], $tasks->handle($c['learner']));
        $c['assignment']->update(['cutoff_at' => now()->addDay()]);
        $this->assertSame(1, $tasks->handle($c['learner'])['needed']);
        $this->submission($c);
        $other = $this->staff($c['school'], SchoolRole::Teacher);
        $this->assertSame(0, app(BuildSchoolTaskSummary::class)->handle($other, $c['school'])['pending']);
        $foreign = $this->context();
        $this->submission($foreign);
        $this->assertSame(1, app(BuildSchoolTaskSummary::class)->handle($c['teacher'], $c['school'])['pending']);
        $this->assertSame(0, app(BuildSchoolTaskSummary::class)->handle($c['teacher'], $foreign['school'])['pending']);
    }

    public function test_deep_review_url_restores_saved_editor_and_excludes_authoring_forms(): void
    {
        $c = $this->context();
        $submission = $this->submission($c);
        SchoolLearningReview::factory()->create(['school_learning_submission_id' => $submission->id, 'feedback' => 'Saved feedback', 'score' => 0, 'maximum_score' => 10]);
        $this->actingAs($c['teacher']);
        Filament::setTenant($c['school']);
        $url = route('filament.school.pages.school-learning', ['tenant' => $c['school']->slug, 'view' => 'review', 'course' => $c['course']->id, 'assignment' => $c['assignment']->id, 'response' => $submission->id]);
        $this->get($url)->assertOk()->assertSee('Saved feedback')->assertSee('Save feedback draft')
            ->assertDontSee('Create a course')->assertDontSee('Save assignment draft')->assertDontSee('Lesson title');
        $this->get($url)->assertOk()->assertSee('Saved feedback');
        foreach (['course=bogus', 'course[]=1', 'view=bogus', 'view=review&response='.$submission->id] as $query) {
            $this->get(route('filament.school.pages.school-learning', ['tenant' => $c['school']->slug]).'?'.$query)->assertNotFound();
        }
        $foreign = $this->context();
        $this->get(route('filament.school.pages.school-learning', ['tenant' => $c['school']->slug, 'course' => $foreign['course']->id]))->assertNotFound();
    }

    public function test_setup_requires_a_connected_path_and_is_admin_only(): void
    {
        $c = $this->context();
        $admin = $this->staff($c['school'], SchoolRole::SchoolAdmin);
        $builder = app(BuildSchoolSetupChecklist::class);
        $steps = $builder->handle($admin, $c['school']);
        $this->assertTrue($steps[6]['complete']);
        $c['placement']->update(['class_group_id' => ClassGroup::factory()->create(['school_id' => $c['school']->id, 'academic_year_id' => $c['year']->id])->id]);
        $this->assertFalse($builder->handle($admin, $c['school'])[6]['complete']);
        $this->actingAs($c['teacher'])->get(route('filament.school.pages.home', ['tenant' => $c['school']->slug]))->assertOk()->assertSee('Learning')->assertDontSee('School demo setup');
        $this->actingAs($admin)->get(route('filament.school.pages.home', ['tenant' => $c['school']->slug]))->assertOk()->assertSee('School demo setup');
        $empty = School::factory()->create();
        $emptyAdmin = $this->staff($empty, SchoolRole::SchoolAdmin);
        foreach ($builder->handle($emptyAdmin, $empty) as $step) {
            $this->assertFalse($step['complete']);
        }
    }

    public function test_livewire_updates_reauthorize_selected_task_after_membership_revocation(): void
    {
        $c = $this->context();
        $this->actingAs($c['teacher']);
        Filament::setTenant($c['school']);
        $component = Livewire::test(SchoolLearning::class)->call('selectCourse', $c['course']->id);
        $c['school']->memberships()->where('user_id', $c['teacher']->id)->update(['status' => 'inactive']);
        $component->call('newDraft')->assertForbidden();
    }

    public function test_school_summary_is_bounded_and_query_count_does_not_grow_with_classes(): void
    {
        $c = $this->context();
        $this->submission($c);
        $builder = app(BuildSchoolTaskSummary::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $builder->handle($c['teacher'], $c['school']);
        $initialQueries = count(DB::getQueryLog());
        for ($i = 0; $i < 6; $i++) {
            $this->submission($this->context($c['school'], $c['teacher']));
        }
        DB::flushQueryLog();
        $summary = $builder->handle($c['teacher'], $c['school']);
        $this->assertSame($initialQueries, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->assertSame(7, $summary['pending']);
        $this->assertCount(5, $summary['responses']);
        foreach ($summary['responses'] as $response) {
            $this->assertArrayNotHasKey('response_text', $response->getAttributes());
            $this->assertArrayNotHasKey('instructions', $response->assignment->getAttributes());
        }
    }

    private function staff(School $school, SchoolRole $role): User
    {
        $user = User::factory()->create();
        $membership = $school->memberships()->create(['user_id' => $user->id, 'status' => 'active', 'joined_at' => now()]);
        $membership->roles()->create(['role' => $role]);

        return $user;
    }

    private function context(?School $school = null, ?User $teacher = null): array
    {
        $school ??= School::factory()->create();
        $teacher ??= $this->staff($school, SchoolRole::Teacher);
        $year = AcademicYear::factory()->create(['school_id' => $school->id, 'status' => 'open']);
        Term::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $class = ClassGroup::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teaching = TeachingAssignment::factory()->create(['school_id' => $school->id, 'class_group_id' => $class->id, 'subject_id' => $subject->id, 'teacher_user_id' => $teacher->id]);
        $course = SchoolCourse::factory()->create(['school_id' => $school->id, 'teaching_assignment_id' => $teaching->id, 'created_by_user_id' => $teacher->id]);
        $lesson = $course->lessons()->create(['position' => 1]);
        $version = SchoolLessonVersion::factory()->create(['school_lesson_id' => $lesson->id, 'created_by_user_id' => $teacher->id, 'status' => 'published', 'published_at' => now()]);
        $assignment = SchoolLearningAssignment::factory()->forPublishedLesson($school, $teaching, $course, $version)->create(['created_by_user_id' => $teacher->id, 'status' => 'published']);
        $learner = User::factory()->create(['account_type' => 'managed_learner', 'learner_activated_at' => now()]);
        $profile = LearnerProfile::factory()->create(['user_id' => $learner->id]);
        $enrolment = Enrolment::factory()->create(['school_id' => $school->id, 'learner_profile_id' => $profile->id]);
        $placement = LearnerClassMembership::factory()->create(['school_id' => $school->id, 'class_group_id' => $class->id, 'enrolment_id' => $enrolment->id, 'starts_on' => today()->subDay()]);
        $recipient = SchoolLearningAssignmentRecipient::factory()->create(['school_id' => $school->id, 'school_learning_assignment_id' => $assignment->id, 'learner_profile_id' => $profile->id, 'enrolment_id' => $enrolment->id, 'learner_class_membership_id' => $placement->id]);

        return compact('school', 'teacher', 'year', 'class', 'teaching', 'course', 'assignment', 'learner', 'profile', 'enrolment', 'placement', 'recipient');
    }

    private function submission(array $c): SchoolLearningSubmission
    {
        return SchoolLearningSubmission::factory()->create(['school_id' => $c['school']->id, 'school_learning_assignment_id' => $c['assignment']->id, 'school_learning_assignment_recipient_id' => $c['recipient']->id, 'learner_profile_id' => $c['profile']->id, 'submitted_by_user_id' => $c['learner']->id, 'status' => 'submitted', 'submitted_at' => now()]);
    }
}
