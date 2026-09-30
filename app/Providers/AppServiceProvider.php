<?php

namespace App\Providers;

use App\Models\GuardianLink;
use App\SchoolRole;
use App\Services\Schools\ClamAvSchoolLessonResourceScanner;
use App\Services\Schools\SchoolLessonResourceScanner;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SchoolLessonResourceScanner::class, ClamAvSchoolLessonResourceScanner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function (ViewContract $view): void {
            $user = auth()->user();

            if ($user === null) {
                $view->with('schoolMemberships', collect());
                $view->with('hasGuardianLinks', false);

                return;
            }

            $view->with('schoolMemberships', $user->schoolMemberships()
                ->active()
                ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('role', [
                    SchoolRole::SchoolAdmin->value,
                    SchoolRole::Teacher->value,
                    SchoolRole::Bursar->value,
                ]))
                ->whereHas('school', fn (Builder $query): Builder => $query->where('status', 'active'))
                ->with('school')
                ->get());
            $view->with('hasGuardianLinks', GuardianLink::query()->eligibleFor($user)->exists());
        });
    }
}
