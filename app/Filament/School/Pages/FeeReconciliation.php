<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\FeeSchedule;
use App\Models\School;
use App\Services\Schools\ReconcileSchoolFees;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class FeeReconciliation extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 41;

    protected static ?string $navigationLabel = 'Fee reconciliation';

    protected static ?string $title = 'Fee reconciliation';

    protected string $view = 'filament.school.pages.fee-reconciliation';

    public static function canAccess(): bool
    {
        $school = Filament::getTenant();

        return $school instanceof School
            && Gate::allows('viewAny', [FeeSchedule::class, $school]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);

        return $school;
    }

    /**
     * @return array{generated_at: Carbon, currencies: Collection}
     */
    public function getReconciliation(): array
    {
        return app(ReconcileSchoolFees::class)->handle($this->getSchool());
    }
}
