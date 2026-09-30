<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PublishSchoolLessonVersion
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

            if ($lockedVersion->status !== SchoolLessonVersionStatus::Draft) {
                throw ValidationException::withMessages(['lesson' => 'Only a draft lesson version can be published.']);
            }

            $resources = $lockedVersion->resources()->get();
            if ($resources->contains(fn ($resource): bool => $resource->status !== SchoolLessonResourceStatus::Clean
                || $resource->storage_key === null
                || $resource->bytes_purged_at !== null)) {
                throw ValidationException::withMessages(['lesson' => 'Wait until every lesson resource has passed validation and malware scanning.']);
            }

            $lesson->versions()
                ->where('status', SchoolLessonVersionStatus::Published->value)
                ->update(['status' => SchoolLessonVersionStatus::Superseded->value]);

            $lockedVersion->forceFill([
                'status' => SchoolLessonVersionStatus::Published,
                'published_at' => now(),
            ])->save();

            $lockedSchool->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'lesson_version.published',
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
