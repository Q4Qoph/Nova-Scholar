<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateSchoolCourse
{
    public function handle(User $actor, School $school, TeachingAssignment $assignment, string $title): SchoolCourse
    {
        Gate::authorize('createForAssignment', [SchoolCourse::class, $assignment]);

        abort_unless($assignment->school_id === $school->id, 404);

        return DB::transaction(function () use ($actor, $school, $assignment, $title): SchoolCourse {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedAssignment = $lockedSchool->teachingAssignments()
                ->whereKey($assignment->id)
                ->where('status', 'active')
                ->firstOrFail();
            Gate::authorize('createForAssignment', [SchoolCourse::class, $lockedAssignment]);

            $existingCourse = $lockedAssignment->lessonCourse()->first();
            if ($existingCourse instanceof SchoolCourse) {
                return $existingCourse;
            }

            $course = $lockedSchool->lessonCourses()->create([
                'teaching_assignment_id' => $lockedAssignment->id,
                'created_by_user_id' => $actor->id,
                'title' => trim($title),
                'status' => 'active',
            ]);

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'lesson_course.created',
                'auditable_type' => SchoolCourse::class,
                'auditable_id' => $course->id,
                'metadata' => ['teaching_assignment_id' => $lockedAssignment->id],
                'occurred_at' => now(),
            ]);

            return $course;
        });
    }
}
