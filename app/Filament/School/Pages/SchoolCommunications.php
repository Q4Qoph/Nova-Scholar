<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\Announcement;
use App\Models\ClassGroup;
use App\Models\School;
use App\Services\Schools\CreateSchoolAnnouncement;
use App\Services\Schools\SendSchoolAnnouncement;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolCommunications extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Communications';

    protected static ?string $title = 'School communications';

    protected static ?string $slug = 'school-communications';

    protected string $view = 'filament.school.pages.school-communications';

    public string $audienceType = 'all_guardians';

    public ?int $classGroupId = null;

    public string $draftTitle = '';

    public string $messageBody = '';

    public static function canAccess(): bool
    {
        $school = Filament::getTenant();

        return $school instanceof School
            && Gate::allows('viewAny', [Announcement::class, $school]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /** @return Collection<int, ClassGroup> */
    public function getActiveClassGroups(): Collection
    {
        return $this->getSchool()->classGroups()
            ->where('class_groups.status', 'active')
            ->orderBy('class_groups.name')
            ->get();
    }

    /** @return Collection<int, Announcement> */
    public function getAnnouncements(): Collection
    {
        return $this->getSchool()->announcements()
            ->with('classGroup')
            ->withCount('deliveries')
            ->latest('id')
            ->get();
    }

    public function updatedAudienceType(): void
    {
        if ($this->audienceType === 'all_guardians') {
            $this->classGroupId = null;
        }
    }

    public function createDraft(CreateSchoolAnnouncement $createSchoolAnnouncement): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Announcement::class, $school]);

        $validated = $this->validate([
            'audienceType' => ['required', Rule::in(['all_guardians', 'class_guardians'])],
            'classGroupId' => [
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('status', 'active')),
            ],
            'draftTitle' => ['required', 'string', 'max:255'],
            'messageBody' => ['required', 'string', 'max:10000'],
        ]);

        if ($validated['audienceType'] === 'class_guardians' && $validated['classGroupId'] === null) {
            throw ValidationException::withMessages([
                'classGroupId' => 'Select an active class for a class notice.',
            ]);
        }

        if ($validated['audienceType'] === 'all_guardians' && $validated['classGroupId'] !== null) {
            throw ValidationException::withMessages([
                'classGroupId' => 'A school-wide notice cannot include a class.',
            ]);
        }

        $announcement = $createSchoolAnnouncement->handle(auth()->user(), $school, [
            'audience_type' => $validated['audienceType'],
            'class_group_id' => $validated['classGroupId'],
            'title' => $validated['draftTitle'],
            'body' => $validated['messageBody'],
        ]);

        $this->reset(['audienceType', 'classGroupId', 'draftTitle', 'messageBody']);
        $this->audienceType = 'all_guardians';

        Notification::make()
            ->success()
            ->title('Draft saved')
            ->body("{$announcement->title} is ready to review before sending.")
            ->send();
    }

    public function sendAnnouncement(int $announcementId, SendSchoolAnnouncement $sendSchoolAnnouncement): void
    {
        $school = $this->getSchool();
        $announcement = $school->announcements()->whereKey($announcementId)->firstOrFail();

        Gate::authorize('send', $announcement);

        $sendSchoolAnnouncement->handle(auth()->user(), $school, $announcement);

        Notification::make()
            ->success()
            ->title('Notice sent')
            ->body('The notice is available in the guardian portal for its current matching audience.')
            ->send();
    }
}
