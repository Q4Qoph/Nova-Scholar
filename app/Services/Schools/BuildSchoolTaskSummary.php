<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningSubmission;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class BuildSchoolTaskSummary
{
    /** @return array{pending: int, responses: Collection<int, SchoolLearningSubmission>} */
    public function handle(User $user, School $school): array
    {
        if (! Gate::forUser($user)->allows('viewAny', [SchoolCourse::class, $school])) {
            return ['pending' => 0, 'responses' => new Collection];
        }
        $isAdmin = $school->memberships()->active()->where('user_id', $user->id)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('role', SchoolRole::SchoolAdmin->value))->exists();
        $query = SchoolLearningSubmission::query()->where('school_id', $school->id)->where('status', 'submitted')
            ->whereHas('assignment', fn (Builder $assignment): Builder => $assignment
                ->where('school_id', $school->id)->where('status', 'published')
                ->whereHas('course', fn (Builder $course): Builder => $course->where('school_id', $school->id)->where('status', 'active'))
                ->whereHas('teachingAssignment', fn (Builder $teaching): Builder => $teaching
                    ->where('school_id', $school->id)->where('status', 'active')
                    ->when(! $isAdmin, fn (Builder $teacher): Builder => $teacher->where('teacher_user_id', $user->id))
                    ->whereHas('classGroup', fn (Builder $class): Builder => $class->where('status', 'active'))))
            ->whereDoesntHave('releasedReview');

        return [
            'pending' => (clone $query)->count(),
            'responses' => $query->select(['id', 'school_id', 'school_learning_assignment_id', 'learner_profile_id', 'submitted_at'])
                ->with(['assignment:id,school_id,school_course_id,title', 'learnerProfile:id,first_name,last_name'])
                ->orderBy('submitted_at')->orderBy('id')->limit(5)->get(),
        ];
    }
}
