<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\Enrolment;
use App\Models\ImportBatch;
use App\Models\School;
use App\Services\Schools\AdmitLearner;
use App\Services\Schools\StageLearnerImport;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LearnerRegistry extends Page
{
    use WithFileUploads;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Learners';

    protected static ?string $title = 'Learner registry';

    protected string $view = 'filament.school.pages.learner-registry';

    public string $firstName = '';

    public string $lastName = '';

    public string $preferredName = '';

    public string $dateOfBirth = '';

    public string $admissionNumber = '';

    public $importFile = null;

    public ?string $importReviewUrl = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', [Enrolment::class, $this->getSchool()]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /**
     * @return LengthAwarePaginator<int, Enrolment>
     */
    public function getLearners(): LengthAwarePaginator
    {
        return $this->getSchool()->enrolments()
            ->with('learnerProfile')
            ->orderByDesc('id')
            ->paginate(50);
    }

    public function canAdmitLearners(): bool
    {
        return Gate::allows('create', [Enrolment::class, $this->getSchool()]);
    }

    public function canImportLearners(): bool
    {
        return Gate::allows('create', [ImportBatch::class, $this->getSchool()]);
    }

    public function stageLearnerImport(StageLearnerImport $stageLearnerImport): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [ImportBatch::class, $school]);

        $validated = $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $batch = $stageLearnerImport->handle(auth()->user(), $school, $validated['importFile']);
        $this->importReviewUrl = route('filament.school.pages.learner-import-review', [
            'tenant' => $school->slug,
            'record' => $batch,
        ]);
        $this->reset('importFile');

        Notification::make()
            ->success()
            ->title('Learner CSV staged')
            ->body('Review the validation results before committing valid rows.')
            ->send();
    }

    public function admitLearner(AdmitLearner $admitLearner): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Enrolment::class, $school]);

        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'preferredName' => ['nullable', 'string', 'max:100'],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'admissionNumber' => [
                'required',
                'string',
                'max:50',
                Rule::unique('enrolments', 'admission_number')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
        ]);

        $learner = $admitLearner->handle(auth()->user(), $school, [
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'preferred_name' => $validated['preferredName'] ?: null,
            'date_of_birth' => $validated['dateOfBirth'] ?: null,
            'admission_number' => $validated['admissionNumber'],
        ]);

        $this->reset(['firstName', 'lastName', 'preferredName', 'dateOfBirth', 'admissionNumber']);
        $this->resetPage();

        Notification::make()
            ->success()
            ->title('Learner admitted')
            ->body("{$learner->admission_number} is now enrolled in this school.")
            ->send();
    }

    public function getDetailUrl(Enrolment $learner): string
    {
        return route('filament.school.pages.learner-detail', [
            'tenant' => $this->getSchool()->slug,
            'record' => $learner,
        ]);
    }
}
