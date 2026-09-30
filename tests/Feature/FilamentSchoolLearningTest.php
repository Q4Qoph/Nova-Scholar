<?php

namespace Tests\Feature;

use App\Filament\School\Pages\SchoolLearning;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSchoolLearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_can_create_course_save_draft_and_preview_it_in_filament(): void
    {
        $school = School::factory()->create(['slug' => 'learning-test-school']);
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
        $this->actingAs($teacher);
        Filament::setTenant($school);

        $this->get('/school/'.$school->slug.'/school-learning')
            ->assertOk()
            ->assertSee('Create a course');

        $component = Livewire::test(SchoolLearning::class)
            ->set('teachingAssignmentId', $assignment->id)
            ->set('courseTitle', 'Grade 5 Mathematics')
            ->call('createCourse')
            ->assertHasNoErrors()
            ->set('lessonTitle', 'Fractions')
            ->set('lessonBody', 'Compare one half and one quarter.')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->call('previewDraft')
            ->assertHasNoErrors()
            ->assertSee('Learner preview · not published')
            ->assertSee('Compare one half and one quarter.');

        $this->assertDatabaseCount('school_courses', 1);
        $this->assertDatabaseHas('school_courses', [
            'school_id' => $school->id,
            'teaching_assignment_id' => $assignment->id,
            'title' => 'Grade 5 Mathematics',
        ]);
        $this->assertDatabaseHas('school_lesson_versions', [
            'title' => 'Fractions',
            'body' => 'Compare one half and one quarter.',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_course.created',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'event_type' => 'lesson_version.draft_saved',
        ]);
    }
}
