<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolCourse;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class SchoolLessonResourcePolicy
{
    public function downloadForLearner(User $user, SchoolLessonResource $resource): bool
    {
        $resource->loadMissing('version.lesson.course');
        $course = $resource->version->lesson->course;

        return $course instanceof SchoolCourse
            && $resource->school_id === $course->school_id
            && $resource->status === SchoolLessonResourceStatus::Clean
            && $resource->storage_key !== null
            && $resource->bytes_purged_at === null
            && ($resource->purge_after === null || $resource->purge_after->isFuture())
            && $resource->version->status === SchoolLessonVersionStatus::Published
            && Gate::allows('viewForLearner', $course);
    }
}
