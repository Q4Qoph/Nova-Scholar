<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\School\Pages\AcademicStructure;
use App\Filament\School\Pages\AttendanceRegister;
use App\Filament\School\Pages\FeeOperations;
use App\Filament\School\Pages\LearnerDetail;
use App\Filament\School\Pages\LearnerImportReview;
use App\Filament\School\Pages\LearnerRegistry;
use App\Filament\School\Pages\SchoolCommunications;
use App\Filament\School\Pages\SchoolDashboard;
use App\Filament\School\Pages\SchoolLearning;
use App\Filament\School\Pages\SchoolOverview;
use App\Filament\School\Pages\SchoolStaffDirectory;
use App\Models\Announcement;
use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\SchoolCourse;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SchoolPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('school')
            ->path('school')
            ->login()
            ->brandName('Nova Scholar School')
            ->viteTheme('resources/css/filament/school/theme.css')
            ->tenant(School::class, 'slug')
            ->userMenuItems([
                Action::make('workspace')->label('Switch workspace')->url(fn (): string => route('workspace'))->icon('heroicon-o-arrows-right-left'),
                Action::make('study')->label('Personal study')->url(fn (): string => route('study'))->icon('heroicon-o-book-open'),
            ])
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(in: app_path('Filament/School/Resources'), for: 'App\\Filament\\School\\Resources')
            ->discoverPages(in: app_path('Filament/School/Pages'), for: 'App\\Filament\\School\\Pages')
            ->pages([
                SchoolDashboard::class,
                SchoolOverview::class,
                LearnerRegistry::class,
                LearnerDetail::class,
                LearnerImportReview::class,
                FeeOperations::class,
                SchoolStaffDirectory::class,
                AcademicStructure::class,
                AttendanceRegister::class,
                SchoolCommunications::class,
                SchoolLearning::class,
            ])
            ->discoverWidgets(in: app_path('Filament/School/Widgets'), for: 'App\\Filament\\School\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->navigationItems($this->schoolWorkflowNavigationItems())
            ->middleware($this->middleware())
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * @return array<int, class-string>
     */
    private function middleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }

    /**
     * @return array<int, NavigationItem>
     */
    private function schoolWorkflowNavigationItems(): array
    {
        return [
            NavigationItem::make('Attendance')
                ->group('School workspace')
                ->sort(35)
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => route('filament.school.pages.attendance-register', ['tenant' => $this->getSchoolTenant()->slug]))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.school.pages.attendance-register'))
                ->visible(fn (): bool => Gate::allows('viewAny', [AttendanceSession::class, $this->getSchoolTenant()])),
            NavigationItem::make('Communications')
                ->group('School workspace')
                ->sort(50)
                ->icon('heroicon-o-megaphone')
                ->url(fn (): string => route('filament.school.pages.school-communications', ['tenant' => $this->getSchoolTenant()->slug]))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.school.pages.school-communications'))
                ->visible(fn (): bool => Gate::allows('viewAny', [Announcement::class, $this->getSchoolTenant()])),
            NavigationItem::make('Learning')
                ->group('School workspace')
                ->sort(45)
                ->icon('heroicon-o-academic-cap')
                ->url(fn (): string => route('filament.school.pages.school-learning', ['tenant' => $this->getSchoolTenant()->slug]))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.school.pages.school-learning'))
                ->visible(fn (): bool => Gate::allows('viewAny', [SchoolCourse::class, $this->getSchoolTenant()])),
        ];
    }

    private function getSchoolTenant(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }
}
