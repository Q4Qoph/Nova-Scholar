<?php

declare(strict_types=1);

namespace App\Filament\Platform\Pages;

use App\Models\School;
use Filament\Pages\Page;

class PlatformOverview extends Page
{
    protected static ?string $navigationLabel = 'Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Platform overview';

    protected string $view = 'filament.platform.pages.platform-overview';

    public function getSchoolDirectoryUrl(): string
    {
        return route('filament.platform.pages.school-directory');
    }

    public function getSchoolsCount(): int
    {
        return School::query()->count();
    }

    public function getActiveSchoolsCount(): int
    {
        return School::query()->where('status', 'active')->count();
    }

    public function getSuspendedSchoolsCount(): int
    {
        return School::query()->where('status', '!=', 'active')->count();
    }
}
