<?php

namespace App\Providers;

use App\SchoolRole;
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
        //
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
                ->whereHas('school', function (Builder $query): void {
                    $query->where('status', 'active');
                })
                ->with('school')
                ->get());
            $view->with('hasGuardianLinks', $user->guardianLinks()
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->whereHas('school', fn (Builder $query): Builder => $query->where('status', 'active'))
                ->whereHas('enrolment', fn (Builder $query): Builder => $query->where('status', 'active'))
                ->exists());
        });
    }
}
