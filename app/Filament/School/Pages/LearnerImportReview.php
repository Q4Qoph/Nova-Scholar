<?php

namespace App\Filament\School\Pages;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\School;
use App\Services\Schools\CommitLearnerImport;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class LearnerImportReview extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Review learner import';

    protected string $view = 'filament.school.pages.learner-import-review';

    public ImportBatch $batch;

    public static function getRoutePath(Panel $panel): string
    {
        return '/learner-import/{record}';
    }

    public function mount(string|int $record): void
    {
        $this->batch = $this->getSchool()->importBatches()
            ->with(['rows' => fn ($query) => $query->orderBy('row_number')])
            ->whereKey($record)
            ->firstOrFail();

        Gate::authorize('view', $this->batch);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /**
     * @return Collection<int, ImportRow>
     */
    public function getRows(): Collection
    {
        return $this->batch->rows;
    }

    public function getRegistryUrl(): string
    {
        return route('filament.school.pages.learner-registry', ['tenant' => $this->getSchool()->slug]);
    }

    public function canCommit(): bool
    {
        return $this->batch->status !== 'committed'
            && $this->batch->valid_rows > 0
            && Gate::allows('commit', $this->batch);
    }

    public function commitImport(CommitLearnerImport $commitLearnerImport): void
    {
        Gate::authorize('commit', $this->batch);

        $this->batch = $commitLearnerImport->handle(auth()->user(), $this->getSchool(), $this->batch)
            ->load(['rows' => fn ($query) => $query->orderBy('row_number')]);

        Notification::make()
            ->success()
            ->title('Learner import processed')
            ->body('Valid rows were committed and invalid rows remain available for review.')
            ->send();
    }
}
