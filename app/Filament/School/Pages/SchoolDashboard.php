<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use Filament\Panel;

class SchoolDashboard extends SchoolOverview
{
    protected static bool $shouldRegisterNavigation = false;

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'home';
    }
}
