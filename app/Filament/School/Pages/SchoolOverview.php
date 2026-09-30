<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\Announcement;
use App\Models\AttendanceSession;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolMembership;
use App\SchoolRole;
use App\Services\Schools\BuildSchoolSetupChecklist;
use App\Services\Schools\BuildSchoolTaskSummary;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

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

    public function getSetupChecklist(): array
    {
        return $this->canManageStaff() ? app(BuildSchoolSetupChecklist::class)->handle(auth()->user(), $this->getSchool()) : [];
    }

    public function getTaskSummary(): array
    {
        return app(BuildSchoolTaskSummary::class)->handle(auth()->user(), $this->getSchool());
    }

    public function getCurrentMembership(): SchoolMembership
    {
        return $this->getSchool()->memberships()
            ->active()
            ->where('user_id', auth()->id())
            ->with('roles')
            ->firstOrFail();
    }

    public function canManageStaff(): bool
    {
        return $this->getCurrentMembership()->roles->contains('role', SchoolRole::SchoolAdmin);
    }

    public function getStaffDirectoryUrl(): string
    {
        return route('filament.school.pages.school-staff-directory', ['tenant' => $this->getSchool()->slug]);
    }

    public function getLearnersUrl(): string
    {
        return route('filament.school.pages.learner-registry', ['tenant' => $this->getSchool()->slug]);
    }

    public function getAcademicsUrl(): string
    {
        return route('filament.school.pages.academic-structure', ['tenant' => $this->getSchool()->slug]);
    }

    public function getAttendanceUrl(): string
    {
        return route('filament.school.pages.attendance-register', ['tenant' => $this->getSchool()->slug]);
    }

    public function getFeesUrl(): string
    {
        return route('filament.school.pages.fee-operations', ['tenant' => $this->getSchool()->slug]);
    }

    public function getCommunicationsUrl(): string
    {
        return route('filament.school.pages.school-communications', ['tenant' => $this->getSchool()->slug]);
    }

    /**
     * @return array<int, array{label: string, description: string, url: string}>
     */
    public function getAvailableWorkflows(): array
    {
        $school = $this->getSchool();
        $workflows = [
            ['label' => 'Learners', 'description' => 'Open the school registry.', 'url' => $this->getLearnersUrl()],
            ['label' => 'Academics', 'description' => 'Manage years, terms, classes and subjects.', 'url' => $this->getAcademicsUrl()],
        ];

        if (Gate::allows('viewAny', [SchoolCourse::class, $school])) {
            $workflows[] = ['label' => 'Learning', 'description' => 'Open courses, assignments and teacher feedback.', 'url' => route('filament.school.pages.school-learning', ['tenant' => $school->slug])];
        }

        if (Gate::allows('viewAny', [FeeSchedule::class, $school])) {
            $workflows[] = ['label' => 'Fees', 'description' => 'Review school fee schedules.', 'url' => $this->getFeesUrl()];
        }

        if (Gate::allows('viewAny', [AttendanceSession::class, $school])) {
            $workflows[] = ['label' => 'Attendance', 'description' => 'Open the protected register.', 'url' => $this->getAttendanceUrl()];
        }

        if (Gate::allows('viewAny', [Announcement::class, $school])) {
            $workflows[] = ['label' => 'Communications', 'description' => 'Draft and send school notices.', 'url' => $this->getCommunicationsUrl()];
        }

        if ($this->canManageStaff()) {
            $workflows[] = ['label' => 'Staff', 'description' => 'Manage school memberships, roles and invitations.', 'url' => $this->getStaffDirectoryUrl()];
        }

        return $workflows;
    }
}
