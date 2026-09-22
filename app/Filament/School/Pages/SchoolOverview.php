<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\School;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class SchoolOverview extends Page
{
    protected static ?string $navigationLabel = 'Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'School overview';

    protected string $view = 'filament.school.pages.school-overview';

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    public function getSchoolOverviewUrl(): string
    {
        return route('schools.overview', $this->getSchool());
    }

    public function getLearnersUrl(): string
    {
        return route('schools.learners.index', $this->getSchool());
    }

    public function getAcademicsUrl(): string
    {
        return route('schools.academic.index', $this->getSchool());
    }

    public function getAttendanceUrl(): string
    {
        return route('schools.attendance.index', $this->getSchool());
    }

    public function getFeesUrl(): string
    {
        return route('schools.fees.index', $this->getSchool());
    }

    public function getCommunicationsUrl(): string
    {
        return route('schools.communication.index', $this->getSchool());
    }
}
