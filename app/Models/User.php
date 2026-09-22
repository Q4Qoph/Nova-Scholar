<?php

namespace App\Models;

use App\SchoolRole;
use App\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'profile_photo_path', 'learning_preferences', 'account_type', 'learner_login_id', 'learner_activated_at', 'learner_deactivated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'learning_preferences' => 'array',
            'password' => 'hashed',
            'role' => UserRole::class,
            'learner_activated_at' => 'datetime',
            'learner_deactivated_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function flashcardDecks(): HasMany
    {
        return $this->hasMany(FlashcardDeck::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function flashcardReviews(): HasMany
    {
        return $this->hasMany(FlashcardReview::class);
    }

    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function sentSchoolInvitations(): HasMany
    {
        return $this->hasMany(SchoolInvitation::class, 'inviter_user_id');
    }

    public function schoolInvitations(): HasMany
    {
        return $this->hasMany(SchoolInvitation::class, 'invitee_user_id');
    }

    public function guardianLinks(): HasMany
    {
        return $this->hasMany(GuardianLink::class, 'guardian_user_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_user_id');
    }

    public function learnerProfile(): HasOne
    {
        return $this->hasOne(LearnerProfile::class);
    }

    public function isManagedLearner(): bool
    {
        return $this->account_type === 'managed_learner';
    }

    public function isActiveManagedLearner(): bool
    {
        return $this->isManagedLearner()
            && $this->learner_activated_at !== null
            && $this->learner_deactivated_at === null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->hasVerifiedEmail() || $this->isActiveManagedLearner()) {
            return false;
        }

        return match ($panel->getId()) {
            'platform' => $this->role === UserRole::Admin,
            'school' => $this->getTenants($panel)->isNotEmpty(),
            default => false,
        };
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof School
            && $tenant->status === 'active'
            && $this->schoolMemberships()
                ->active()
                ->where('school_id', $tenant->getKey())
                ->whereHas('roles', fn ($query) => $query->whereIn('role', [
                    SchoolRole::SchoolAdmin->value,
                    SchoolRole::Teacher->value,
                    SchoolRole::Bursar->value,
                ]))
                ->exists();
    }

    /**
     * @return Collection<int, School>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($panel->getId() !== 'school') {
            return collect();
        }

        return School::query()
            ->where('status', 'active')
            ->whereHas('memberships', function ($query): void {
                $query->active()
                    ->where('user_id', $this->getKey())
                    ->whereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('role', [
                        SchoolRole::SchoolAdmin->value,
                        SchoolRole::Teacher->value,
                        SchoolRole::Bursar->value,
                    ]));
            })
            ->orderBy('name')
            ->get();
    }
}
