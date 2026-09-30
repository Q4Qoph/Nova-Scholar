<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLearningAssignment;
use App\Models\SchoolLearningAssignmentStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WithdrawSchoolLessonVersion
{
    public function handle(User $actor, School $school, SchoolLessonVersion $version): SchoolLessonVersion
    {
        return DB::transaction(function () use ($actor, $school, $version): SchoolLessonVersion {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedVersion = SchoolLessonVersion::query()
                ->whereKey($version->id)
                ->whereHas('lesson.course', fn ($query) => $query->where('school_id', $lockedSchool->id))
                ->lockForUpdate()
                ->firstOrFail();
            $lesson = $lockedVersion->lesson()->lockForUpdate()->firstOrFail();
            $course = SchoolCourse::query()->whereKey($lesson->school_course_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $course);

            if ($lockedVersion->status !== SchoolLessonVersionStatus::Published) {
                throw ValidationException::withMessages(['lesson' => 'Only the currently published lesson version can be withdrawn.']);
            }

            $lockedVersion->forceFill([
                'status' => SchoolLessonVersionStatus::Withdrawn,
                'withdrawn_at' => now(),
            ])->save();

            $lockedVersion->resources()->whereNull('purge_after')->update(['purge_after' => now()->addDays(90)]);

            $affectedAssignments = $lockedSchool->learningAssignments()
                ->where('source_lesson_version_id', $lockedVersion->id)
                ->whereIn('status', [
                    SchoolLearningAssignmentStatus::Draft->value,
                    SchoolLearningAssignmentStatus::Published->value,
                ])
                ->get();

            foreach ($affectedAssignments as $assignment) {
                $assignment->forceFill(['status' => SchoolLearningAssignmentStatus::Withdrawn])->save();

                $lockedSchool->auditEvents()->create([
                    'actor_user_id' => $actor->id,
                    'event_type' => 'school_learning_assignment.withdrawn',
                    'auditable_type' => SchoolLearningAssignment::class,
                    'auditable_id' => $assignment->id,
                    'metadata' => ['source_lesson_version_id' => $lockedVersion->id],
                    'occurred_at' => now(),
                ]);
            }

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'lesson_version.withdrawn',
                'auditable_type' => SchoolLessonVersion::class,
                'auditable_id' => $lockedVersion->id,
                'metadata' => [
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'version_number' => $lockedVersion->version_number,
                ],
                'occurred_at' => now(),
            ]);

            return $lockedVersion->refresh();
        });
    }
}
