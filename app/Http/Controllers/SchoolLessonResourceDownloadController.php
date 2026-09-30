<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceStatus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolLessonResourceDownloadController extends Controller
{
    public function learner(SchoolLessonResource $resource): StreamedResponse
    {
        abort_unless(Gate::allows('downloadForLearner', $resource), 404);

        return $this->download($resource);
    }

    public function staff(School $school, SchoolLessonResource $lessonResource): StreamedResponse
    {
        $resource = $lessonResource;
        abort_unless($resource->school_id === $school->id, 404);

        $resource->loadMissing('version.lesson.course');
        $course = $resource->version->lesson->course;
        abort_unless($course->school_id === $school->id, 404);

        Gate::authorize('update', $course);
        abort_unless($resource->status === SchoolLessonResourceStatus::Clean
            && $resource->storage_key !== null
            && $resource->bytes_purged_at === null
            && ($resource->purge_after === null || $resource->purge_after->isFuture()), 404);

        return $this->download($resource);
    }

    private function download(SchoolLessonResource $resource): StreamedResponse
    {
        abort_unless($resource->status === SchoolLessonResourceStatus::Clean
            && $resource->storage_key !== null
            && $resource->bytes_purged_at === null
            && ($resource->purge_after === null || $resource->purge_after->isFuture())
            && Storage::disk($resource->storage_disk)->exists($resource->storage_key), 404);

        return Storage::disk($resource->storage_disk)->download($resource->storage_key, $resource->display_name, [
            'Content-Type' => $resource->media_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
