<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Jobs\ScanSchoolLessonResourceJob;
use App\Models\School;
use App\Models\SchoolCourse;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceRightsBasis;
use App\Models\SchoolLessonResourceStatus;
use App\Models\SchoolLessonVersion;
use App\Models\SchoolLessonVersionStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreSchoolLessonResource
{
    private const MAX_RESOURCES = 500;

    private const MAX_STORAGE_BYTES = 2 * 1024 * 1024 * 1024;

    public function __construct(private readonly ValidateSchoolLessonResourceUpload $validateUpload) {}

    public function handle(
        User $actor,
        School $school,
        SchoolLessonVersion $version,
        UploadedFile $upload,
        SchoolLessonResourceRightsBasis $rightsBasis,
        ?string $rightsReference,
    ): SchoolLessonResource {
        $lessonVersion = $version->loadMissing('lesson.course.teachingAssignment');
        $course = $lessonVersion->lesson->course;

        if (! $course instanceof SchoolCourse || $course->school_id !== $school->id) {
            throw new AuthorizationException('This lesson version is outside the selected school.');
        }

        Gate::authorize('update', $course);

        if ($lessonVersion->status !== SchoolLessonVersionStatus::Draft) {
            throw ValidationException::withMessages(['upload' => 'Resources can only be attached to a lesson draft.']);
        }

        if (in_array($rightsBasis, [SchoolLessonResourceRightsBasis::Licensed, SchoolLessonResourceRightsBasis::PermissionGranted], true)
            && trim((string) $rightsReference) === '') {
            throw ValidationException::withMessages(['rightsReference' => 'Add the licence or permission source.']);
        }

        $validatedUpload = $this->validateUpload->handle($upload);
        $byteSize = filesize($validatedUpload->path);
        if ($byteSize === false || $byteSize < 1 || $byteSize > 10 * 1024 * 1024) {
            $this->deleteTemporaryUpload($validatedUpload);

            throw ValidationException::withMessages(['upload' => 'The processed upload must be between 1 byte and 10 MB.']);
        }

        $safeName = $this->safeDisplayName($upload->getClientOriginalName(), $validatedUpload->extension);
        $storageKey = 'school-lesson-resources/quarantine/'.Str::uuid().'.'.$validatedUpload->extension;
        $checksum = hash_file('sha256', $validatedUpload->path);

        if (! is_string($checksum)) {
            $this->deleteTemporaryUpload($validatedUpload);

            throw ValidationException::withMessages(['upload' => 'The processed upload could not be verified.']);
        }

        try {
            $resource = DB::transaction(function () use (
                $actor,
                $school,
                $lessonVersion,
                $rightsBasis,
                $rightsReference,
                $safeName,
                $storageKey,
                $validatedUpload,
                $byteSize,
                $checksum,
            ): SchoolLessonResource {
                $lockedSchool = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
                $lockedVersion = SchoolLessonVersion::query()
                    ->whereKey($lessonVersion->id)
                    ->whereHas('lesson.course', fn ($query) => $query->where('school_id', $lockedSchool->id))
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedVersion->status !== SchoolLessonVersionStatus::Draft) {
                    throw ValidationException::withMessages(['upload' => 'Resources can only be attached to a lesson draft.']);
                }

                $course = $lockedVersion->lesson->course;
                Gate::authorize('update', $course);

                $storedResources = $lockedSchool->lessonResources()->whereNull('bytes_purged_at');
                $resourceCount = (clone $storedResources)->count();
                $storedBytes = (int) (clone $storedResources)->sum('byte_size');

                if ($resourceCount >= self::MAX_RESOURCES || $storedBytes + $byteSize > self::MAX_STORAGE_BYTES) {
                    throw ValidationException::withMessages(['upload' => 'This school has reached its private resource storage limit.']);
                }

                $resource = $lockedSchool->lessonResources()->create([
                    'school_lesson_version_id' => $lockedVersion->id,
                    'uploaded_by_user_id' => $actor->id,
                    'display_name' => $safeName,
                    'storage_disk' => 'local',
                    'storage_key' => $storageKey,
                    'media_type' => $validatedUpload->mediaType,
                    'byte_size' => $byteSize,
                    'sha256' => $checksum,
                    'status' => SchoolLessonResourceStatus::Quarantined,
                    'rights_basis' => $rightsBasis,
                    'rights_reference' => $rightsReference !== null ? trim($rightsReference) : null,
                    'rights_attested_at' => now(),
                ]);

                $lockedSchool->auditEvents()->create([
                    'actor_user_id' => $actor->id,
                    'event_type' => 'lesson_resource.quarantined',
                    'auditable_type' => SchoolLessonResource::class,
                    'auditable_id' => $resource->id,
                    'metadata' => [
                        'lesson_version_id' => $lockedVersion->id,
                        'rights_basis' => $rightsBasis->value,
                    ],
                    'occurred_at' => now(),
                ]);

                return $resource;
            });
        } catch (Throwable $exception) {
            $this->deleteTemporaryUpload($validatedUpload);

            throw $exception;
        }

        try {
            $stream = fopen($validatedUpload->path, 'rb');
            if (! is_resource($stream)) {
                throw new \RuntimeException('The processed upload could not be opened.');
            }

            try {
                $stored = Storage::disk('local')->put($storageKey, $stream, ['visibility' => 'private']);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new \RuntimeException('Private storage did not accept the upload.');
            }

            $resource->forceFill([
                'status' => SchoolLessonResourceStatus::ScanPending,
                'validated_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storageKey);
            $resource->forceFill([
                'status' => SchoolLessonResourceStatus::Rejected,
                'storage_key' => null,
                'byte_size' => 0,
                'scan_result_code' => 'storage_failed',
                'bytes_purged_at' => now(),
            ])->save();

            throw ValidationException::withMessages(['upload' => 'Private storage is unavailable. Try again later.']);
        } finally {
            $this->deleteTemporaryUpload($validatedUpload);
        }

        try {
            ScanSchoolLessonResourceJob::dispatch($resource->id)->afterCommit();
        } catch (Throwable $exception) {
            $resource->forceFill(['scan_result_code' => 'scan_dispatch_failed'])->save();

            throw ValidationException::withMessages(['upload' => 'The resource is safely stored, but scanning could not be queued. Retry the scan from the resource list.']);
        }

        return $resource->refresh();
    }

    private function safeDisplayName(string $originalName, string $extension): string
    {
        $baseName = basename(str_replace('\\', '/', $originalName));
        $baseName = preg_replace('/[\x00-\x1F\x7F]/u', '', $baseName) ?? '';
        $baseName = trim(pathinfo($baseName, PATHINFO_FILENAME));
        $baseName = mb_substr($baseName !== '' ? $baseName : 'resource', 0, 230);

        return $baseName.'.'.$extension;
    }

    private function deleteTemporaryUpload(ValidatedSchoolLessonUpload $upload): void
    {
        if ($upload->temporaryPath && is_file($upload->path)) {
            @unlink($upload->path);
        }
    }
}
