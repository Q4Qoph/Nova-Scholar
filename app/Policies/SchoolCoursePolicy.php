<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;

class SchoolCoursePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $this->hasRole($user, $school, [SchoolRole::SchoolAdmin, SchoolRole::Teacher]);
    }

    public function create(User $user, School $school): bool
    {
        return $this->hasRole($user, $school, [SchoolRole::SchoolAdmin, SchoolRole::Teacher]);
    }

    public function createForAssignment(User $user, TeachingAssignment $assignment): bool
    {
        return $assignment->status === 'active'
            && $assignment->school->status === 'active'
            && $assignment->classGroup->status === 'active'
            && ($assignment->teacher_user_id === $user->id
                ? $this->hasRole($user, $assignment->school, [SchoolRole::Teacher])
                : $this->hasRole($user, $assignment->school, [SchoolRole::SchoolAdmin]));
    }

    public function view(User $user, SchoolCourse $course): bool
    {
        return $course->school_id === $course->teachingAssignment->school_id
            && $course->status === 'active'
            && $this->canManageAssignment($user, $course->teachingAssignment);
    }

    public function update(User $user, SchoolCourse $course): bool
    {
        return $this->view($user, $course);
    }

    public function viewForLearner(User $user, SchoolCourse $course): bool
    {
        $assignment = $course->teachingAssignment;
        $learnerProfile = $user->learnerProfile;

        if ($course->school_id !== $assignment->school_id
            || $course->status !== 'active'
            || $course->school->status !== 'active'
            || $assignment->status !== 'active'
            || $assignment->classGroup->status !== 'active'
            || $learnerProfile === null
            || $learnerProfile->status !== 'active') {
            return false;
        }

        return $learnerProfile->enrolments()
            ->where('school_id', $course->school_id)
            ->where('status', 'active')
            ->whereHas('classMemberships', fn ($query) => $query
                ->where('school_id', $course->school_id)
                ->where('class_group_id', $assignment->class_group_id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', now()->toDateString())
                ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()->toDateString())))
            ->exists();
    }

    private function canManageAssignment(User $user, TeachingAssignment $assignment): bool
    {
        return $assignment->status === 'active'
            && $assignment->classGroup->status === 'active'
            && $this->hasRole($user, $assignment->school, [SchoolRole::Teacher, SchoolRole::SchoolAdmin])
            && ($assignment->teacher_user_id === $user->id
                || $this->hasRole($user, $assignment->school, [SchoolRole::SchoolAdmin]));
    }

    /** @param array<int, SchoolRole> $roles */
    private function hasRole(User $user, School $school, array $roles): bool
    {
        if ($school->status !== 'active') {
            return false;
        }

        $roleValues = array_map(fn (SchoolRole $role): string => $role->value, $roles);

        return $user->schoolMemberships()
            ->active()
            ->where('school_id', $school->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('role', $roleValues))
            ->exists();
    }
}
