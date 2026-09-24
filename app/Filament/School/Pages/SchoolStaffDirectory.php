<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\SchoolMembership;
use App\Models\User;
use App\SchoolRole;
use App\Services\Schools\CreateSchoolInvitation;
use App\Services\Schools\ManageSchoolRole;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class SchoolStaffDirectory extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $title = 'Staff directory';

    protected string $view = 'filament.school.pages.school-staff-directory';

    public string $inviteeEmail = '';

    public string $inviteeRole = SchoolRole::Teacher->value;

    /**
     * @var array<int, string>
     */
    public array $roleToAssign = [];

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    /**
     * @return Collection<int, SchoolMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->getSchool()->memberships()
            ->active()
            ->with(['user', 'roles'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, SchoolInvitation>
     */
    public function getInvitations(): Collection
    {
        return $this->getSchool()->invitations()
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->with('invitee')
            ->latest('created_at')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function getInviteeOptions(): Collection
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('schoolMemberships', fn ($query) => $query->active()->where('school_id', $this->getSchool()->id))
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);
    }

    public function canManageInvitations(): bool
    {
        return $this->getSchool()->memberships()
            ->active()
            ->where('user_id', auth()->id())
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
            ->exists();
    }

    public function createInvitation(CreateSchoolInvitation $createSchoolInvitation): void
    {
        $school = $this->getSchool();

        abort_unless($this->canManageInvitations(), 403);

        $validated = $this->validate([
            'inviteeEmail' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(fn ($query) => $query->whereNotNull('email_verified_at')),
            ],
            'inviteeRole' => ['required', Rule::in([
                SchoolRole::SchoolAdmin->value,
                SchoolRole::Teacher->value,
                SchoolRole::Bursar->value,
            ])],
        ]);

        $created = $createSchoolInvitation->handle(
            auth()->user(),
            $school,
            $validated['inviteeEmail'],
            SchoolRole::from($validated['inviteeRole']),
        );

        $this->reset(['inviteeEmail']);

        Notification::make()
            ->success()
            ->title('Invitation created')
            ->body(route('school-invitations.show', $created['token']))
            ->send();
    }

    public function assignRole(int $membershipId, ManageSchoolRole $manageSchoolRole): void
    {
        $membership = $this->getSchool()->memberships()->whereKey($membershipId)->firstOrFail();

        abort_unless($this->canManageInvitations(), 403);

        $validated = $this->validate([
            "roleToAssign.{$membershipId}" => ['required', Rule::in([
                SchoolRole::SchoolAdmin->value,
                SchoolRole::Teacher->value,
                SchoolRole::Bursar->value,
            ])],
        ]);

        try {
            $manageSchoolRole->assign(
                auth()->user(),
                $membership,
                SchoolRole::from($validated['roleToAssign'][$membershipId]),
            );
        } catch (AuthorizationException $exception) {
            Notification::make()->danger()->title('Role not assigned')->body($exception->getMessage())->send();

            return;
        }

        unset($this->roleToAssign[$membershipId]);

        Notification::make()->success()->title('Role assigned')->send();
    }

    public function removeRole(int $membershipId, string $role, ManageSchoolRole $manageSchoolRole): void
    {
        $membership = $this->getSchool()->memberships()->whereKey($membershipId)->firstOrFail();
        $schoolRole = SchoolRole::tryFrom($role);

        abort_unless($this->canManageInvitations() && $schoolRole?->isStaffRole(), 403);

        try {
            $manageSchoolRole->remove(auth()->user(), $membership, $schoolRole);
        } catch (AuthorizationException $exception) {
            Notification::make()->danger()->title('Role not removed')->body($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Role removed')->send();
    }

    public function revokeInvitation(int $invitationId, CreateSchoolInvitation $createSchoolInvitation): void
    {
        $school = $this->getSchool();
        $invitation = $school->invitations()->whereKey($invitationId)->firstOrFail();

        abort_unless($this->canManageInvitations(), 403);

        try {
            $createSchoolInvitation->revoke(auth()->user(), $invitation);
        } catch (AuthorizationException $exception) {
            Notification::make()->danger()->title('Invitation not revoked')->body($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Invitation revoked')->send();
    }
}
