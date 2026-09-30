<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaveSchoolLessonDraft
{
    public function handle(
        User $actor,
        School $school,
        SchoolCourse $course,
        string $title,
        string $body,
        ?int $lessonId = null,
    ): SchoolLessonVersion {
        return DB::transaction(function () use ($actor, $school, $course, $title, $body, $lessonId): SchoolLessonVersion {
            $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedCourse = $lockedSchool->lessonCourses()->whereKey($course->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $lockedCourse);

            if ($lessonId === null) {
                $lesson = $lockedCourse->lessons()->create([
                    'position' => (int) $lockedCourse->lessons()->max('position') + 1,
                ]);
            } else {
                $lesson = $lockedCourse->lessons()->whereKey($lessonId)->lockForUpdate()->firstOrFail();
            }

            $draft = $lesson->versions()
                ->where('status', SchoolLessonVersionStatus::Draft->value)
                ->latest('version_number')
                ->lockForUpdate()
                ->first();

            if ($draft instanceof SchoolLessonVersion) {
                $draft->forceFill(['title' => trim($title), 'body' => $body])->save();
            } else {
                $versionNumber = (int) $lesson->versions()->max('version_number') + 1;
                $draft = $lesson->versions()->create([
                    'version_number' => $versionNumber,
                    'title' => trim($title),
                    'body' => $body,
                    'status' => SchoolLessonVersionStatus::Draft,
                    'created_by_user_id' => $actor->id,
                ]);
            }

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'lesson_version.draft_saved',
                'auditable_type' => SchoolLessonVersion::class,
                'auditable_id' => $draft->id,
                'metadata' => ['course_id' => $lockedCourse->id, 'lesson_id' => $lesson->id],
                'occurred_at' => now(),
            ]);

            return $draft;
        });
    }
}
