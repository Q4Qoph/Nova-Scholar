<?php

namespace App\Filament\School\Pages;

use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\SchoolRole;
use App\Services\Schools\CreateManagedLearnerAccess;
use App\Services\Schools\DeactivateLearner;
use App\Services\Schools\LinkGuardian;
use App\Services\Schools\PromoteLearner;
use App\Services\Schools\RevokeGuardianLink;
use App\Services\Schools\TransferLearner;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LearnerDetail extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Learner record';

    protected string $view = 'filament.school.pages.learner-detail';

    public Enrolment $learner;

    public string $guardianEmail = '';

    public string $guardianRelationship = '';

    public ?string $activationUrl = null;

    public string $deactivatedOn = '';

    public ?int $promotionClassGroupId = null;

    public string $promotionStartsOn = '';

    public ?int $transferDestinationSchoolId = null;

    public string $transferAdmissionNumber = '';

    public string $transferDate = '';

    public static function getRoutePath(Panel $panel): string
    {
        return '/learner/{record}';
    }

    public function mount(string|int $record): void
    {
        $this->learner = $this->getSchool()->enrolments()
            ->with([
                'learnerProfile.user',
                'guardianLinks.guardian',
                'classMemberships.classGroup.academicYear',
            ])
            ->whereKey($record)
            ->firstOrFail();

        Gate::authorize('view', $this->learner);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    public function getRegistryUrl(): string
    {
        return route('filament.school.pages.learner-registry', ['tenant' => $this->getSchool()->slug]);
    }

    public function getManagementUrl(): string
    {
        return route('schools.learners.show', [$this->getSchool(), $this->learner]);
    }

    /**
     * @return Collection<int, GuardianLink>
     */
    public function getGuardianLinks(): Collection
    {
        return $this->learner->guardianLinks->where('status', 'active')->values();
    }

    /**
     * @return Collection<int, LearnerClassMembership>
     */
    public function getClassMemberships(): Collection
    {
        return $this->learner->classMemberships->sortByDesc('starts_on')->values();
    }

    public function canManageGuardians(): bool
    {
        return Gate::allows('create', [GuardianLink::class, $this->getSchool(), $this->learner]);
    }

    public function canManageAccess(): bool
    {
        return Gate::allows('create', [Enrolment::class, $this->getSchool()]);
    }

    public function canManageLifecycle(): bool
    {
        return Gate::allows('create', [Enrolment::class, $this->getSchool()])
            && $this->learner->status === 'active';
    }

    /**
     * @return Collection<int, ClassGroup>
     */
    public function getPromotionClassGroups(): Collection
    {
        return $this->getSchool()->classGroups()
            ->where('class_groups.status', 'active')
            ->with('academicYear')
            ->orderByDesc('class_groups.academic_year_id')
            ->orderBy('class_groups.name')
            ->get();
    }

    public function canManagePlacements(): bool
    {
        return Gate::allows('create', [LearnerClassMembership::class, $this->getSchool()])
            && $this->learner->status === 'active';
    }

    public function promoteLearner(PromoteLearner $promoteLearner): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [LearnerClassMembership::class, $school]);

        $validated = $this->validate([
            'promotionClassGroupId' => [
                'required',
                'integer',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query
                    ->where('school_id', $school->id)
                    ->where('status', 'active')),
            ],
            'promotionStartsOn' => ['required', 'date'],
        ]);

        $promoteLearner->handle(auth()->user(), $school, $this->learner, [
            'class_group_id' => $validated['promotionClassGroupId'],
            'starts_on' => $validated['promotionStartsOn'],
        ]);

        $this->learner->load(['classMemberships.classGroup.academicYear']);
        $this->reset(['promotionClassGroupId', 'promotionStartsOn']);
        Notification::make()->success()->title('Learner promoted')->send();
    }

    /**
     * @return Collection<int, School>
     */
    public function getTransferSchools(): SupportCollection
    {
        return auth()->user()->schoolMemberships()
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->whereHas('school', fn ($query) => $query
                ->where('status', 'active')
                ->where('id', '!=', $this->getSchool()->id))
            ->with('school')
            ->get()
            ->pluck('school')
            ->unique('id')
            ->values();
    }

    public function canTransferLearner(): bool
    {
        return $this->canManageLifecycle() && $this->getTransferSchools()->isNotEmpty();
    }

    public function transferLearner(TransferLearner $transferLearner): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Enrolment::class, $school]);

        $validated = $this->validate([
            'transferDestinationSchoolId' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'transferAdmissionNumber' => [
                'required',
                'string',
                'max:50',
                Rule::unique('enrolments', 'admission_number')->where(fn ($query) => $query->where('school_id', $this->transferDestinationSchoolId ?? 0)),
            ],
            'transferDate' => ['required', 'date'],
        ]);

        $transferLearner->handle(auth()->user(), $school, $this->learner, [
            'destination_school_id' => $validated['transferDestinationSchoolId'],
            'admission_number' => $validated['transferAdmissionNumber'],
            'transferred_on' => $validated['transferDate'],
        ]);

        $this->learner->refresh()->load(['learnerProfile.user', 'guardianLinks.guardian', 'classMemberships.classGroup.academicYear']);
        $this->reset(['transferDestinationSchoolId', 'transferAdmissionNumber', 'transferDate']);
        Notification::make()->success()->title('Learner transferred')->body('The destination enrolment was created and source history retained.')->send();
    }

    public function deactivateLearner(DeactivateLearner $deactivateLearner): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Enrolment::class, $school]);

        $validated = $this->validate([
            'deactivatedOn' => ['required', 'date'],
        ]);

        $deactivateLearner->handle(auth()->user(), $school, $this->learner, $validated['deactivatedOn']);
        $this->learner->refresh()->load(['learnerProfile.user', 'guardianLinks.guardian', 'classMemberships.classGroup.academicYear']);
        $this->deactivatedOn = '';

        Notification::make()->success()->title('Learner deactivated')->body('The enrolment history has been retained.')->send();
    }

    public function issueManagedAccess(CreateManagedLearnerAccess $createManagedLearnerAccess): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [Enrolment::class, $school]);

        $result = $createManagedLearnerAccess->handle(auth()->user(), $school, $this->learner);
        $this->learner->load('learnerProfile.user');
        $this->activationUrl = route('learner.activate.show', $result['token']);

        Notification::make()
            ->success()
            ->title('Managed learner access issued')
            ->body('Share the one-time activation link securely with the learner.')
            ->send();
    }

    public function linkGuardian(LinkGuardian $linkGuardian): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [GuardianLink::class, $school, $this->learner]);

        $validated = $this->validate([
            'guardianEmail' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(fn ($query) => $query->whereNotNull('email_verified_at')),
            ],
            'guardianRelationship' => ['required', 'string', 'max:50'],
        ]);

        try {
            $linkGuardian->handle(auth()->user(), $school, $this->learner, $validated['guardianEmail'], $validated['guardianRelationship']);
        } catch (AuthorizationException $exception) {
            Notification::make()->danger()->title('Guardian not linked')->body($exception->getMessage())->send();

            return;
        }

        $this->learner->load('guardianLinks.guardian');
        $this->reset(['guardianEmail', 'guardianRelationship']);
        Notification::make()->success()->title('Guardian relationship verified')->send();
    }

    public function revokeGuardian(int $guardianLinkId, RevokeGuardianLink $revokeGuardianLink): void
    {
        $guardianLink = $this->learner->guardianLinks()->whereKey($guardianLinkId)->firstOrFail();

        abort_unless($guardianLink->school_id === $this->getSchool()->id, 404);
        Gate::authorize('delete', $guardianLink);

        $revokeGuardianLink->handle(auth()->user(), $guardianLink);
        $this->learner->load('guardianLinks.guardian');
        Notification::make()->success()->title('Guardian relationship revoked')->send();
    }
}
