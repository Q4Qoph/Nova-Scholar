<?php

namespace App\Filament\School\Pages;

use App\Models\FeeCharge;
use App\Models\FeeChargeBatch;
use App\Models\FeeSchedule;
use App\Models\School;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class FeeOperations extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Fees';

    protected static ?string $title = 'Fee operations';

    protected string $view = 'filament.school.pages.fee-operations';

    public function mount(): void
    {
        Gate::authorize('viewAny', [FeeSchedule::class, $this->getSchool()]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /**
     * @return Collection<int, FeeSchedule>
     */
    public function getSchedules(): Collection
    {
        return $this->getSchool()->feeSchedules()
            ->with(['term', 'classGroup'])
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, FeeChargeBatch>
     */
    public function getBatches(): Collection
    {
        return $this->getSchool()->feeChargeBatches()
            ->with('feeSchedule')
            ->withCount('charges')
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, FeeCharge>
     */
    public function getRecentCharges(): Collection
    {
        return $this->getSchool()->feeCharges()
            ->with('enrolment.learnerProfile')
            ->latest('id')
            ->limit(20)
            ->get();
    }
}
