<?php

declare(strict_types=1);

namespace App\Filament\Platform\Pages;

use App\Models\School;
use App\Models\User;
use App\Services\Schools\ProvisionSchool;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use UnitEnum;

class SchoolDirectory extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Schools';

    protected static string|UnitEnum|null $navigationGroup = 'Platform operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'School directory';

    protected string $view = 'filament.platform.pages.school-directory';

    public string $schoolName = '';

    public string $schoolSlug = '';

    public string $schoolType = 'day';

    public string $timezone = 'Africa/Nairobi';

    public ?int $administratorId = null;

    #[Computed]
    public function schools(): Collection
    {
        return School::query()
            ->withCount('memberships')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function administrators(): Collection
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);
    }

    public function provisionSchool(ProvisionSchool $provisionSchool): void
    {
        $validated = $this->validate([
            'schoolName' => ['required', 'string', 'max:255'],
            'schoolSlug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('schools', 'slug')],
            'schoolType' => ['required', Rule::in(['day', 'boarding', 'mixed'])],
            'timezone' => ['required', 'timezone:all'],
            'administratorId' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereNotNull('email_verified_at')),
            ],
        ]);

        $administrator = User::query()
            ->whereKey($validated['administratorId'])
            ->whereNotNull('email_verified_at')
            ->firstOrFail();

        $school = $provisionSchool->handle(
            auth()->user(),
            [
                'name' => $validated['schoolName'],
                'slug' => $validated['schoolSlug'],
                'school_type' => $validated['schoolType'],
                'timezone' => $validated['timezone'],
            ],
            $administrator,
        );

        $this->reset(['schoolName', 'schoolSlug', 'administratorId']);
        $this->schoolType = 'day';
        $this->timezone = 'Africa/Nairobi';
        unset($this->schools);

        Notification::make()
            ->success()
            ->title("{$school->name} provisioned")
            ->body('The first school administrator has been assigned.')
            ->send();
    }
}
