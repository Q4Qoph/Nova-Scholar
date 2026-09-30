<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Jobs\ScanSchoolLessonResourceJob;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLearningSubmission;
use App\Models\SchoolLessonResourceRightsBasis;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\TeachingAssignment;
use App\SchoolRole;
use App\Services\Schools\CreateSchoolCourse;
use App\Services\Schools\PublishSchoolLearningAssignment;
use App\Services\Schools\PublishSchoolLessonVersion;
use App\Services\Schools\ReleaseSchoolLearningReview;
use App\Services\Schools\SaveSchoolLearningAssignmentDraft;
use App\Services\Schools\SaveSchoolLearningReview;
use App\Services\Schools\SaveSchoolLessonDraft;
use App\Services\Schools\StoreSchoolLessonResource;
use App\Services\Schools\WithdrawSchoolLessonVersion;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SchoolLearning extends Page
{
    use WithFileUploads;
    use WithPagination;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 45;

    protected static ?string $navigationLabel = 'Learning';

    protected static ?string $title = 'School learning';

    protected static ?string $slug = 'school-learning';

    protected string $view = 'filament.school.pages.school-learning';

    public ?int $teachingAssignmentId = null;

    public ?int $courseId = null;

    public ?int $lessonId = null;

    public ?int $versionId = null;

    public string $courseTitle = '';

    public string $lessonTitle = '';

    public string $lessonBody = '';

    public ?TemporaryUploadedFile $upload = null;

    public string $rightsBasis = 'educator_created';

    public string $rightsReference = '';

    public bool $rightsAttested = false;

    public bool $previewing = false;

    public ?int $assignmentLessonVersionId = null;

    public ?int $learningAssignmentId = null;

    public string $assignmentTitle = '';

    public string $assignmentInstructions = '';

    public string $assignmentDueAt = '';

    public string $assignmentCutoffAt = '';

    public ?int $reviewAssignmentId = null;

    public ?int $reviewSubmissionId = null;

    public string $reviewFeedback = '';

    public string $reviewScore = '';

    public string $reviewMaximumScore = '';

    public string $taskView = 'courses';

    public function mount(): void
    {
        $mode = request()->query('view', 'courses');
        abort_unless(is_string($mode) && in_array($mode, ['courses', 'lessons', 'assignments', 'review'], true), 404);
        $this->taskView = $mode;
        foreach (['course' => 'courseId', 'assignment' => 'reviewAssignmentId', 'response' => 'reviewSubmissionId'] as $key => $property) {
            $value = request()->query($key);
            if ($value !== null) {
                abort_unless(is_string($value) && ctype_digit($value) && (int) $value > 0 && strlen($value) < 19, 404);
                $this->{$property} = (int) $value;
            }
        }
        $this->validateTaskContext();
        if ($this->reviewSubmissionId !== null) {
            $this->openSubmissionReview($this->reviewSubmissionId);
        }
    }

    public function hydrate(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validateTaskContext();
    }

    public function validateTaskContext(): void
    {
        abort_unless(in_array($this->taskView, ['courses', 'lessons', 'assignments', 'review'], true), 404);
        if ($this->courseId !== null) {
            abort_unless($this->getSelectedCourse() !== null, 404);
        }
        if ($this->reviewAssignmentId !== null) {
            $this->getReviewAssignment();
        }
        if ($this->reviewSubmissionId !== null) {
            $this->getReviewSubmission();
        }
    }

    public function taskUrl(string $mode, ?int $courseId = null, ?int $assignmentId = null, ?int $responseId = null): string
    {
        return route('filament.school.pages.school-learning', array_filter([
            'tenant' => $this->getSchool()->slug, 'view' => $mode,
            'course' => $courseId ?? $this->courseId,
            'assignment' => $assignmentId, 'response' => $responseId,
            'reviewPage' => $mode === 'review' ? $this->getPage('reviewPage') : null,
        ], fn ($value): bool => $value !== null));
    }

    public static function canAccess(): bool
    {
        $school = Filament::getTenant();

        return $school instanceof School && Gate::allows('viewAny', [SchoolCourse::class, $school]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /** @return Collection<int, TeachingAssignment> */
    public function getTeachingAssignments(): Collection
    {
        $user = auth()->user();
        $isSchoolAdmin = $user->schoolMemberships()
            ->active()
            ->where('school_id', $this->getSchool()->id)
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();

        return $this->getSchool()->teachingAssignments()
            ->where('status', 'active')
            ->whereHas('classGroup', fn ($query) => $query->where('status', 'active'))
            ->when(! $isSchoolAdmin, fn ($query) => $query->where('teacher_user_id', $user->id))
            ->with(['classGroup.academicYear', 'subject', 'teacher'])
            ->orderBy('class_group_id')
            ->orderBy('subject_id')
            ->get();
    }

    /** @return Collection<int, SchoolCourse> */
    public function getCourses(): Collection
    {
        $assignmentIds = $this->getTeachingAssignments()->modelKeys();

        return $this->getSchool()->lessonCourses()
            ->whereIn('teaching_assignment_id', $assignmentIds)
            ->with(['teachingAssignment.classGroup', 'teachingAssignment.subject'])
            ->orderBy('title')
            ->get();
    }

    /** @return array{count: int, bytes: int, count_percent: int, bytes_percent: int} */
    public function getResourceUsage(): array
    {
        $storedResources = $this->getSchool()->lessonResources()->whereNull('bytes_purged_at');
        $count = (clone $storedResources)->count();
        $bytes = (int) (clone $storedResources)->sum('byte_size');

        return [
            'count' => $count,
            'bytes' => $bytes,
            'count_percent' => (int) ceil($count / 500 * 100),
            'bytes_percent' => (int) ceil($bytes / (2 * 1024 * 1024 * 1024) * 100),
        ];
    }

    public function getSelectedCourse(): ?SchoolCourse
    {
        if ($this->courseId === null) {
            return null;
        }

        $course = $this->getSchool()->lessonCourses()
            ->whereIn('teaching_assignment_id', $this->getTeachingAssignments()->modelKeys())
            ->whereKey($this->courseId)
            ->with(['teachingAssignment.classGroup', 'teachingAssignment.subject', 'lessons.versions.resources'])->first();
        if (! $course instanceof SchoolCourse) {
            return null;
        }

        Gate::authorize('view', $course);

        return $course;
    }

    /** @return Collection<int, SchoolLearningAssignment> */
    public function getLearningAssignments(): Collection
    {
        $course = $this->getSelectedCourse();
        if (! $course instanceof SchoolCourse) {
            return new Collection;
        }

        return $this->getSchool()->learningAssignments()
            ->where('school_course_id', $course->id)
            ->with('sourceLessonVersion.lesson')
            ->withCount('recipients')
            ->withCount(['submissions as submitted_count' => fn ($query) => $query->where('status', 'submitted')])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getReviewAssignment(): ?SchoolLearningAssignment
    {
        if ($this->reviewAssignmentId === null) {
            return null;
        }
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);
        $assignment = $this->getSchool()->learningAssignments()
            ->where('school_course_id', $course->id)
            ->where('status', 'published')
            ->whereKey($this->reviewAssignmentId)->firstOrFail();
        Gate::authorize('view', $assignment);

        return $assignment;
    }

    /** @return LengthAwarePaginator<int, SchoolLearningSubmission>|null */
    public function getReviewSubmissions(): ?LengthAwarePaginator
    {
        $assignment = $this->getReviewAssignment();
        if ($assignment === null) {
            return null;
        }

        return $assignment->submissions()
            ->where('school_learning_submissions.school_id', $this->getSchool()->id)
            ->where('school_learning_submissions.status', 'submitted')
            ->with(['learnerProfile', 'review'])
            ->orderBy('school_learning_submissions.submitted_at')
            ->orderBy('school_learning_submissions.id')
            ->paginate(20, ['school_learning_submissions.*'], 'reviewPage');
    }

    public function getReviewSubmission(): ?SchoolLearningSubmission
    {
        if ($this->reviewSubmissionId === null) {
            return null;
        }
        $assignment = $this->getReviewAssignment();
        abort_unless($assignment !== null, 404);
        $submission = $assignment->submissions()
            ->where('school_learning_submissions.school_id', $this->getSchool()->id)
            ->where('school_learning_submissions.id', $this->reviewSubmissionId)
            ->with(['learnerProfile', 'review'])->firstOrFail();
        Gate::authorize('review', $submission);

        return $submission;
    }

    public function openAssignmentSubmissions(int $assignmentId): void
    {
        $this->taskView = 'review';
        $this->reviewAssignmentId = $assignmentId;
        $this->getReviewAssignment();
        $this->reset(['reviewSubmissionId', 'reviewFeedback', 'reviewScore', 'reviewMaximumScore']);
        $this->resetValidation();
        $this->resetPage('reviewPage');
    }

    public function openSubmissionReview(int $submissionId): void
    {
        $this->reviewSubmissionId = $submissionId;
        $submission = $this->getReviewSubmission();
        $this->reviewFeedback = $submission->review?->feedback ?? '';
        $this->reviewScore = $submission->review?->score === null ? '' : (string) $submission->review->score;
        $this->reviewMaximumScore = $submission->review?->maximum_score === null ? '' : (string) $submission->review->maximum_score;
        $this->resetValidation();
        $this->dispatch('draft-context-loaded');
    }

    public function saveSubmissionReview(SaveSchoolLearningReview $saveSchoolLearningReview): void
    {
        $submission = $this->getReviewSubmission();
        abort_unless($submission !== null, 404);
        $saveSchoolLearningReview->handle(auth()->user(), $this->getSchool(), $submission, [
            'feedback' => $this->reviewFeedback,
            'score' => $this->reviewScore,
            'maximum_score' => $this->reviewMaximumScore,
        ]);
        $this->openSubmissionReview($submission->id);
        $this->dispatch('draft-saved');
        Notification::make()->success()->title('Feedback draft saved')->body('The learner cannot see this draft.')->send();
    }

    public function releaseSubmissionReview(ReleaseSchoolLearningReview $releaseSchoolLearningReview): void
    {
        $submission = $this->getReviewSubmission();
        abort_unless($submission !== null, 404);
        $review = $submission->review;
        if ($review !== null && $review->released_at === null
            && (trim($this->reviewFeedback) !== $review->feedback
                || $this->reviewScore !== ($review->score === null ? '' : (string) $review->score)
                || $this->reviewMaximumScore !== ($review->maximum_score === null ? '' : (string) $review->maximum_score))) {
            throw ValidationException::withMessages(['reviewFeedback' => 'Save your changes before releasing feedback.']);
        }
        $releaseSchoolLearningReview->handle(auth()->user(), $this->getSchool(), $submission);
        $this->openSubmissionReview($submission->id);
        Notification::make()->success()->title('Feedback released')->body('The learner can now read this feedback.')->send();
    }

    public function createCourse(CreateSchoolCourse $createSchoolCourse): void
    {
        $school = $this->getSchool();
        Gate::authorize('create', [SchoolCourse::class, $school]);

        $validated = $this->validate([
            'teachingAssignmentId' => [
                'required',
                'integer',
                Rule::exists('teaching_assignments', 'id')
                    ->where(fn ($query) => $query->where('school_id', $school->id)->where('status', 'active')),
            ],
            'courseTitle' => ['required', 'string', 'max:255'],
        ]);

        $assignment = $this->getTeachingAssignments()->firstWhere('id', $validated['teachingAssignmentId']);
        if (! $assignment instanceof TeachingAssignment) {
            throw ValidationException::withMessages([
                'teachingAssignmentId' => 'Choose an active assignment available to your account.',
            ]);
        }

        $course = $createSchoolCourse->handle(auth()->user(), $school, $assignment, $validated['courseTitle']);
        $this->taskView = 'lessons';
        $this->courseId = $course->id;
        $this->courseTitle = '';

        $this->dispatch('task-context-changed', url: $this->taskUrl('lessons', $course->id));
        $this->dispatch('draft-saved');
        Notification::make()->success()->title('Course created')->send();
    }

    public function newAssignmentDraft(): void
    {
        $this->reset([
            'assignmentLessonVersionId',
            'learningAssignmentId',
            'assignmentTitle',
            'assignmentInstructions',
            'assignmentDueAt',
            'assignmentCutoffAt',
        ]);
        $this->dispatch('draft-context-loaded');
    }

    public function editAssignmentDraft(int $assignmentId): void
    {
        $this->taskView = 'assignments';
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $assignment = $this->getSchool()->learningAssignments()
            ->where('school_course_id', $course->id)
            ->whereKey($assignmentId)
            ->firstOrFail();
        Gate::authorize('view', $assignment);
        abort_unless($assignment->status === SchoolLearningAssignmentStatus::Draft->value, 404);

        $timezone = $this->getSchool()->timezone;
        $this->learningAssignmentId = $assignment->id;
        $this->assignmentLessonVersionId = $assignment->source_lesson_version_id;
        $this->assignmentTitle = $assignment->title;
        $this->assignmentInstructions = $assignment->instructions;
        $this->assignmentDueAt = $assignment->due_at->timezone($timezone)->format('Y-m-d\\TH:i');
        $this->assignmentCutoffAt = $assignment->cutoff_at?->timezone($timezone)->format('Y-m-d\\TH:i') ?? '';
        $this->dispatch('draft-context-loaded');
    }

    public function saveAssignmentDraft(SaveSchoolLearningAssignmentDraft $saveSchoolLearningAssignmentDraft): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $validated = $this->validate([
            'assignmentLessonVersionId' => ['required', 'integer'],
            'assignmentTitle' => ['required', 'string', 'max:255'],
            'assignmentInstructions' => ['required', 'string', 'max:10000'],
            'assignmentDueAt' => ['required', 'date'],
            'assignmentCutoffAt' => ['nullable', 'date'],
        ]);

        $assignment = $saveSchoolLearningAssignmentDraft->handle(
            auth()->user(),
            $this->getSchool(),
            $course->teachingAssignment,
            $course,
            (int) $validated['assignmentLessonVersionId'],
            $validated['assignmentTitle'],
            $validated['assignmentInstructions'],
            $validated['assignmentDueAt'],
            $validated['assignmentCutoffAt'] ?: null,
            $this->learningAssignmentId,
        );

        $this->learningAssignmentId = $assignment->id;
        $this->dispatch('draft-saved');
        Notification::make()->success()->title('Assignment draft saved')->send();
    }

    public function publishLearningAssignment(int $assignmentId, PublishSchoolLearningAssignment $publishSchoolLearningAssignment): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $assignment = $this->getSchool()->learningAssignments()
            ->where('school_course_id', $course->id)
            ->whereKey($assignmentId)
            ->firstOrFail();
        $publishedAssignment = $publishSchoolLearningAssignment->handle(auth()->user(), $this->getSchool(), $assignment);

        $this->learningAssignmentId = null;
        Notification::make()
            ->success()
            ->title('Assignment published')
            ->body($publishedAssignment->recipients_count.' learners added from the current class roster.')
            ->send();
    }

    public function selectCourse(int $courseId): void
    {
        $course = $this->getSchool()->lessonCourses()->whereKey($courseId)->firstOrFail();
        Gate::authorize('view', $course);
        $this->taskView = 'lessons';
        $this->courseId = $course->id;
        $this->reset(['reviewAssignmentId', 'reviewSubmissionId', 'reviewFeedback', 'reviewScore', 'reviewMaximumScore']);
        $this->resetPage('reviewPage');
        $this->newDraft();
    }

    public function newDraft(): void
    {
        $this->reset(['lessonId', 'versionId', 'lessonTitle', 'lessonBody', 'upload', 'rightsReference', 'rightsAttested']);
        $this->rightsBasis = SchoolLessonResourceRightsBasis::EducatorCreated->value;
        $this->previewing = false;
        $this->dispatch('draft-context-loaded');
    }

    public function openDraft(int $versionId): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $version = $course->lessons()->whereHas('versions', fn ($query) => $query->whereKey($versionId))
            ->with('versions.resources')
            ->firstOrFail()
            ->versions
            ->firstWhere('id', $versionId);

        abort_unless($version instanceof SchoolLessonVersion && $version->status === SchoolLessonVersionStatus::Draft, 404);

        $this->lessonId = $version->school_lesson_id;
        $this->versionId = $version->id;
        $this->lessonTitle = $version->title;
        $this->lessonBody = $version->body;
        $this->previewing = false;
        $this->dispatch('draft-context-loaded');
    }

    public function startNewVersion(int $lessonId): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $lesson = $course->lessons()->whereKey($lessonId)->with('versions')->firstOrFail();
        $latestVersion = $lesson->versions->sortByDesc('version_number')->first();
        abort_unless($latestVersion instanceof SchoolLessonVersion, 404);

        $this->lessonId = $lesson->id;
        $this->versionId = null;
        $this->lessonTitle = $latestVersion->title;
        $this->lessonBody = $latestVersion->body;
        $this->previewing = false;
        $this->dispatch('draft-context-loaded');
    }

    public function previewDraft(): void
    {
        $this->validate([
            'lessonTitle' => ['required', 'string', 'max:255'],
            'lessonBody' => ['required', 'string', 'max:50000'],
        ]);

        $this->previewing = true;
    }

    public function saveDraft(SaveSchoolLessonDraft $saveSchoolLessonDraft): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $validated = $this->validate([
            'lessonTitle' => ['required', 'string', 'max:255'],
            'lessonBody' => ['required', 'string', 'max:50000'],
        ]);

        $version = $saveSchoolLessonDraft->handle(
            auth()->user(),
            $this->getSchool(),
            $course,
            $validated['lessonTitle'],
            $validated['lessonBody'],
            $this->lessonId,
        );

        $this->lessonId = $version->school_lesson_id;
        $this->versionId = $version->id;
        $this->previewing = false;

        $this->dispatch('draft-saved');
        Notification::make()->success()->title('Draft saved')->send();
    }

    public function uploadResource(StoreSchoolLessonResource $storeSchoolLessonResource): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse && $this->versionId !== null, 404);

        $rightsBasisValues = array_map(fn (SchoolLessonResourceRightsBasis $basis): string => $basis->value, SchoolLessonResourceRightsBasis::cases());
        $validated = $this->validate([
            'upload' => ['required', 'file', 'max:10240', 'mimes:pdf,docx,txt,jpg,jpeg,png', 'extensions:pdf,docx,txt,jpg,jpeg,png'],
            'rightsBasis' => ['required', Rule::in($rightsBasisValues)],
            'rightsReference' => ['nullable', 'string', 'max:512'],
            'rightsAttested' => ['accepted'],
        ]);

        $rightsBasis = SchoolLessonResourceRightsBasis::from($validated['rightsBasis']);
        if (in_array($rightsBasis, [SchoolLessonResourceRightsBasis::Licensed, SchoolLessonResourceRightsBasis::PermissionGranted], true)
            && trim((string) $validated['rightsReference']) === '') {
            throw ValidationException::withMessages(['rightsReference' => 'Add the licence or permission source.']);
        }

        $version = $course->lessons()
            ->whereKey($this->lessonId)
            ->firstOrFail()
            ->versions()
            ->whereKey($this->versionId)
            ->where('status', SchoolLessonVersionStatus::Draft->value)
            ->firstOrFail();

        $resource = $storeSchoolLessonResource->handle(
            auth()->user(),
            $this->getSchool(),
            $version,
            $this->upload,
            $rightsBasis,
            $validated['rightsReference'] ?: null,
        );

        $this->reset(['upload', 'rightsReference', 'rightsAttested']);
        $this->rightsBasis = SchoolLessonResourceRightsBasis::EducatorCreated->value;

        Notification::make()
            ->success()
            ->title('Resource quarantined')
            ->body('Learners cannot access it until validation and malware scanning succeed.')
            ->send();
    }

    public function publishVersion(int $versionId, PublishSchoolLessonVersion $publishSchoolLessonVersion): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $version = $course->lessons()
            ->whereHas('versions', fn ($query) => $query->whereKey($versionId))
            ->firstOrFail()
            ->versions()
            ->whereKey($versionId)
            ->firstOrFail();

        $publishSchoolLessonVersion->handle(auth()->user(), $this->getSchool(), $version);
        $this->reset(['lessonId', 'versionId', 'lessonTitle', 'lessonBody']);

        Notification::make()->success()->title('Lesson published')->send();
    }

    public function withdrawVersion(int $versionId, WithdrawSchoolLessonVersion $withdrawSchoolLessonVersion): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $version = $course->lessons()
            ->whereHas('versions', fn ($query) => $query->whereKey($versionId))
            ->firstOrFail()
            ->versions()
            ->whereKey($versionId)
            ->firstOrFail();

        $withdrawSchoolLessonVersion->handle(auth()->user(), $this->getSchool(), $version);

        Notification::make()->success()->title('Lesson withdrawn')->send();
    }

    public function retryResourceScan(int $resourceId): void
    {
        $course = $this->getSelectedCourse();
        abort_unless($course instanceof SchoolCourse, 404);

        $resource = $course->lessons()
            ->whereHas('versions.resources', fn ($query) => $query
                ->whereKey($resourceId)
                ->where('status', SchoolLessonResourceStatus::ScanPending->value))
            ->with('versions.resources')
            ->get()
            ->flatMap(fn ($lesson) => $lesson->versions)
            ->flatMap(fn ($version) => $version->resources)
            ->firstWhere('id', $resourceId);

        abort_unless($resource !== null, 404);
        Gate::authorize('update', $course);

        ScanSchoolLessonResourceJob::dispatch($resource->id)->afterCommit();

        Notification::make()->success()->title('Scan queued')->send();
    }
}
