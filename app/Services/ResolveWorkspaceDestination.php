<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuardianLink;
use App\Models\User;
use App\UserRole;
use Filament\Facades\Filament;

class ResolveWorkspaceDestination
{
    /** @return array<int, array{label: string, url: string}> */
    public function destinations(User $user): array
    {
        if ($user->role === UserRole::Admin) {
            return [['label' => 'Platform workspace', 'url' => route('filament.platform.home')]];
        }

        $destinations = $user->getTenants(Filament::getPanel('school'))
            ->map(fn ($school): array => ['label' => $school->name, 'url' => route('filament.school.pages.home', ['tenant' => $school->slug])])
            ->all();

        if (GuardianLink::query()->eligibleFor($user)->exists()) {
            $destinations[] = ['label' => 'My children', 'url' => route('guardian.learners.index')];
        }

        return $destinations;
    }
}
