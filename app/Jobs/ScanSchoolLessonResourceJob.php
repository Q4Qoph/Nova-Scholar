<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceStatus;
use App\Services\Schools\SchoolLessonResourceScanner;
use App\Services\Schools\SchoolLessonResourceScanVerdict;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScanSchoolLessonResourceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 45;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public readonly int $resourceId) {}

    public function failed(?Throwable $exception): void
    {
        SchoolLessonResource::query()
            ->whereKey($this->resourceId)
            ->where('status', SchoolLessonResourceStatus::ScanPending->value)
            ->whereNull('bytes_purged_at')
            ->update(['scan_result_code' => 'scanner_unavailable']);
    }

    public function handle(SchoolLessonResourceScanner $scanner): void
    {
        $resource = SchoolLessonResource::query()
            ->whereKey($this->resourceId)
            ->where('status', SchoolLessonResourceStatus::ScanPending->value)
            ->whereNull('bytes_purged_at')
            ->first();

        if (! $resource instanceof SchoolLessonResource || $resource->storage_key === null) {
            return;
        }

        $disk = Storage::disk($resource->storage_disk);
        if (! $disk->exists($resource->storage_key)) {
            $this->markRejected($resource, 'object_missing');

            return;
        }

        $result = $scanner->scan($disk->path($resource->storage_key));

        if ($result->verdict === SchoolLessonResourceScanVerdict::Unavailable) {
            $resource->forceFill(['scan_result_code' => 'scanner_unavailable'])->save();

            throw new \RuntimeException('The configured malware scanner is unavailable.');
        }

        if ($result->verdict === SchoolLessonResourceScanVerdict::Infected) {
            $this->markRejected($resource, 'infected', $result->scannerName, $result->signatureVersion);

            return;
        }

        DB::transaction(function () use ($resource, $result): void {
            $school = School::query()->whereKey($resource->school_id)->lockForUpdate()->firstOrFail();
            $lockedResource = $school->lessonResources()
                ->whereKey($resource->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedResource->status !== SchoolLessonResourceStatus::ScanPending
                || $lockedResource->bytes_purged_at !== null
                || $lockedResource->storage_key === null) {
                return;
            }

            $lockedResource->forceFill([
                'status' => SchoolLessonResourceStatus::Clean,
                'scanned_at' => now(),
                'scanner_name' => $result->scannerName,
                'scanner_signature_version' => $result->signatureVersion,
                'scan_result_code' => 'clean',
            ])->save();

            $school->auditEvents()->create([
                'actor_user_id' => null,
                'event_type' => 'lesson_resource.scan_clean',
                'auditable_type' => SchoolLessonResource::class,
                'auditable_id' => $lockedResource->id,
                'metadata' => ['scanner' => $result->scannerName],
                'occurred_at' => now(),
            ]);
        });
    }

    private function markRejected(
        SchoolLessonResource $resource,
        string $resultCode,
        ?string $scannerName = null,
        ?string $signatureVersion = null,
    ): void {
        $cleanup = DB::transaction(function () use ($resource, $resultCode, $scannerName, $signatureVersion): ?array {
            $school = School::query()->whereKey($resource->school_id)->lockForUpdate()->firstOrFail();
            $lockedResource = $school->lessonResources()
                ->whereKey($resource->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedResource->bytes_purged_at !== null) {
                return null;
            }

            $wasAlreadyRejected = $lockedResource->status === SchoolLessonResourceStatus::Rejected;

            $lockedResource->forceFill([
                'status' => SchoolLessonResourceStatus::Rejected,
                'scanned_at' => now(),
                'scanner_name' => $scannerName,
                'scanner_signature_version' => $signatureVersion,
                'scan_result_code' => $resultCode,
                'purge_after' => now(),
            ])->save();

            if (! $wasAlreadyRejected) {
                $school->auditEvents()->create([
                    'actor_user_id' => null,
                    'event_type' => 'lesson_resource.scan_rejected',
                    'auditable_type' => SchoolLessonResource::class,
                    'auditable_id' => $lockedResource->id,
                    'metadata' => ['result' => $resultCode],
                    'occurred_at' => now(),
                ]);
            }

            return [
                'disk' => $lockedResource->storage_disk,
                'key' => $lockedResource->storage_key,
            ];
        });

        if ($cleanup === null) {
            return;
        }

        $deleted = $cleanup['key'] === null
            || Storage::disk($cleanup['disk'])->delete($cleanup['key']);

        if (! $deleted) {
            return;
        }

        DB::transaction(function () use ($resource, $cleanup): void {
            $school = School::query()->whereKey($resource->school_id)->lockForUpdate()->firstOrFail();
            $lockedResource = $school->lessonResources()
                ->whereKey($resource->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedResource->status !== SchoolLessonResourceStatus::Rejected
                || $lockedResource->bytes_purged_at !== null
                || $lockedResource->storage_key !== $cleanup['key']) {
                return;
            }

            $lockedResource->forceFill([
                'storage_key' => null,
                'bytes_purged_at' => now(),
            ])->save();
        });
    }
}
