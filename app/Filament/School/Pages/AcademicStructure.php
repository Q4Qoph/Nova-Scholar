<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\CreateAcademicYear;
use App\Services\Schools\CreateClassGroup;
use App\Services\Schools\CreateSubject;
use App\Services\Schools\CreateTeachingAssignment;
use App\Services\Schools\CreateTerm;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicStructure extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Academics';

    protected static ?string $title = 'Academic structure';

    protected string $view = 'filament.school.pages.academic-structure';

    public string $academicYearName = '';

    public string $academicYearStartsOn = '';

    public string $academicYearEndsOn = '';

    public string $subjectName = '';

    public string $subjectCode = '';

    /**
     * @var array<int, string>
     */
    public array $termNames = [];

    /**
     * @var array<int, string>
     */
    public array $termStartsOn = [];

    /**
     * @var array<int, string>
     */
    public array $termEndsOn = [];

    /**
     * @var array<int, string>
     */
    public array $classNames = [];

    /**
     * @var array<int, string>
     */
    public array $classGradeLevels = [];

    /**
     * @var array<int, string>
     */
    public array $classStreams = [];

    public ?int $assignmentClassGroupId = null;

    public ?int $assignmentSubjectId = null;

    public ?int $assignmentTeacherUserId = null;

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    public function getAcademicYears(): Collection
    {
        return $this->getSchool()->academicYears()
            ->with(['terms', 'classGroups'])
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
    }

    public function canManageAcademicYears(): bool
    {
        return Gate::allows('create', [AcademicYear::class, $this->getSchool()]);
    }

    public function createAcademicYear(CreateAcademicYear $createAcademicYear): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [AcademicYear::class, $school]);

        $validated = $this->validate([
            'academicYearName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('academic_years', 'name')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
            'academicYearStartsOn' => ['required', 'date'],
            'academicYearEndsOn' => ['required', 'date', 'after_or_equal:academicYearStartsOn'],
        ]);

        $academicYear = $createAcademicYear->handle(auth()->user(), $school, [
            'name' => $validated['academicYearName'],
            'starts_on' => $validated['academicYearStartsOn'],
            'ends_on' => $validated['academicYearEndsOn'],
        ]);

        $this->reset(['academicYearName', 'academicYearStartsOn', 'academicYearEndsOn']);

        Notification::make()
            ->success()
            ->title("{$academicYear->name} created")
            ->body('The academic year is now available in this school workspace.')
            ->send();
    }

    public function canManageSubjects(): bool
    {
        return Gate::allows('create', [Subject::class, $this->getSchool()]);
    }

    public function createSubject(CreateSubject $createSubject): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Subject::class, $school]);

        $this->subjectCode = strtoupper($this->subjectCode);

        $validated = $this->validate([
            'subjectName' => ['required', 'string', 'max:100'],
            'subjectCode' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('subjects', 'code')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
        ]);

        $subject = $createSubject->handle(auth()->user(), $school, [
            'name' => $validated['subjectName'],
            'code' => $validated['subjectCode'],
        ]);

        $this->reset(['subjectName', 'subjectCode']);

        Notification::make()
            ->success()
            ->title("{$subject->name} added")
            ->body("{$subject->code} is now available in this school workspace.")
            ->send();
    }

    public function canManageTerms(): bool
    {
        return $this->canManageAcademicYears();
    }

    public function createTerm(int $academicYearId, CreateTerm $createTerm): void
    {
        $school = $this->getSchool();
        $academicYear = $school->academicYears()->whereKey($academicYearId)->firstOrFail();

        Gate::authorize('create', [Term::class, $academicYear]);

        $validated = $this->validate([
            "termNames.{$academicYearId}" => [
                'required',
                'string',
                'max:50',
                Rule::unique('terms', 'name')->where(fn ($query) => $query->where('academic_year_id', $academicYear->id)),
            ],
            "termStartsOn.{$academicYearId}" => ['required', 'date'],
            "termEndsOn.{$academicYearId}" => ['required', 'date', "after_or_equal:termStartsOn.{$academicYearId}"],
        ]);

        $startsOn = $validated['termStartsOn'][$academicYearId];
        $endsOn = $validated['termEndsOn'][$academicYearId];

        if (Term::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('starts_on', '<=', $endsOn)
            ->where('ends_on', '>=', $startsOn)
            ->exists()) {
            throw ValidationException::withMessages([
                "termStartsOn.{$academicYearId}" => 'The term dates overlap an existing term.',
            ]);
        }

        $term = $createTerm->handle(auth()->user(), $school, $academicYear, [
            'name' => $validated['termNames'][$academicYearId],
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ]);

        unset($this->termNames[$academicYearId], $this->termStartsOn[$academicYearId], $this->termEndsOn[$academicYearId]);

        Notification::make()
            ->success()
            ->title("{$term->name} added")
            ->body("{$academicYear->name} now includes this term.")
            ->send();
    }

    public function canManageClassGroups(): bool
    {
        return $this->canManageAcademicYears();
    }

    public function createClassGroup(int $academicYearId, CreateClassGroup $createClassGroup): void
    {
        $school = $this->getSchool();
        $academicYear = $school->academicYears()->whereKey($academicYearId)->firstOrFail();

        Gate::authorize('create', [ClassGroup::class, $academicYear]);

        $validated = $this->validate([
            "classNames.{$academicYearId}" => [
                'required',
                'string',
                'max:80',
                Rule::unique('class_groups', 'name')->where(fn ($query) => $query->where('academic_year_id', $academicYear->id)),
            ],
            "classGradeLevels.{$academicYearId}" => ['required', 'string', 'max:50'],
            "classStreams.{$academicYearId}" => ['nullable', 'string', 'max:50'],
        ]);

        $classGroup = $createClassGroup->handle(auth()->user(), $school, $academicYear, [
            'name' => $validated['classNames'][$academicYearId],
            'grade_level' => $validated['classGradeLevels'][$academicYearId],
            'stream' => $validated['classStreams'][$academicYearId] ?? null,
        ]);

        unset($this->classNames[$academicYearId], $this->classGradeLevels[$academicYearId], $this->classStreams[$academicYearId]);

        Notification::make()
            ->success()
            ->title("{$classGroup->name} added")
            ->body("{$academicYear->name} now includes this class group.")
            ->send();
    }

    /**
     * @return Collection<int, ClassGroup>
     */
    public function getAssignableClassGroups(): Collection
    {
        return $this->getSchool()->classGroups()
            ->where('class_groups.status', 'active')
            ->with('academicYear')
            ->orderBy('class_groups.name')
            ->get();
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getAssignableSubjects(): Collection
    {
        return $this->getSchool()->subjects()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function getAssignableTeachers(): Collection
    {
        return User::query()
            ->whereHas('schoolMemberships', function ($query): void {
                $query->active()
                    ->where('school_id', $this->getSchool()->id)
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('role', SchoolRole::Teacher->value));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);
    }

    public function canManageTeachingAssignments(): bool
    {
        return Gate::allows('create', [TeachingAssignment::class, $this->getSchool()]);
    }

    public function createTeachingAssignment(CreateTeachingAssignment $createTeachingAssignment): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [TeachingAssignment::class, $school]);

        $validated = $this->validate([
            'assignmentClassGroupId' => [
                'required',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $school->id)),
                Rule::unique('teaching_assignments', 'class_group_id')->where(
                    fn ($query) => $query
                        ->where('subject_id', $this->assignmentSubjectId)
                        ->where('teacher_user_id', $this->assignmentTeacherUserId)
                ),
            ],
            'assignmentSubjectId' => [
                'required',
                Rule::exists('subjects', 'id')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
            'assignmentTeacherUserId' => [
                'required',
                'integer',
                Rule::exists('school_memberships', 'user_id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('status', 'active')
                    ->whereNull('removed_at')),
            ],
        ]);

        $teacherIsActive = $school->memberships()
            ->active()
            ->where('user_id', $validated['assignmentTeacherUserId'])
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::Teacher->value))
            ->exists();

        if (! $teacherIsActive) {
            throw ValidationException::withMessages([
                'assignmentTeacherUserId' => 'The selected user is not an active teacher in this school.',
            ]);
        }

        $assignment = $createTeachingAssignment->handle(auth()->user(), $school, [
            'class_group_id' => $validated['assignmentClassGroupId'],
            'subject_id' => $validated['assignmentSubjectId'],
            'teacher_user_id' => $validated['assignmentTeacherUserId'],
        ]);

        $this->reset(['assignmentClassGroupId', 'assignmentSubjectId', 'assignmentTeacherUserId']);

        Notification::make()
            ->success()
            ->title('Teaching assignment created')
            ->body("{$assignment->classGroup->name} is now assigned for {$assignment->subject->name}.")
            ->send();
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->getSchool()->subjects()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, TeachingAssignment>
     */
    public function getAssignments(): Collection
    {
        return $this->getSchool()->teachingAssignments()
            ->with(['classGroup', 'subject', 'teacher'])
            ->orderByDesc('id')
            ->get();
    }
}
