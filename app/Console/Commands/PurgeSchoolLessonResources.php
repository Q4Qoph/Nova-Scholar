<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonResourceStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('school:purge-lesson-resources')]
#[Description('Purge expired private school lesson resource bytes.')]
class PurgeSchoolLessonResources extends Command
{
    public function handle(): int
    {
        $purgedCount = 0;

        SchoolLessonResource::query()
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', now())
            ->whereNull('bytes_purged_at')
            ->orderBy('id')
            ->chunkById(100, function ($resources) use (&$purgedCount): void {
                foreach ($resources as $resource) {
                    if ($this->purge($resource)) {
                        $purgedCount++;
                    }
                }
            });

        $this->info("Purged {$purgedCount} private lesson resource(s).");

        return self::SUCCESS;
    }

    private function purge(SchoolLessonResource $resource): bool
    {
        $storageKey = DB::transaction(function () use ($resource): ?string {
            $school = School::query()->whereKey($resource->school_id)->lockForUpdate()->first();
            if (! $school instanceof School) {
                return null;
            }

            $lockedResource = $school->lessonResources()
                ->whereKey($resource->id)
                ->lockForUpdate()
                ->first();
            if (! $lockedResource instanceof SchoolLessonResource
                || $lockedResource->bytes_purged_at !== null
                || $lockedResource->purge_after === null
                || $lockedResource->purge_after->isFuture()) {
                return null;
            }

            if ($lockedResource->storage_key === null) {
                $lockedResource->forceFill([
                    'status' => SchoolLessonResourceStatus::Purged,
                    'bytes_purged_at' => now(),
                ])->save();

                $school->auditEvents()->create([
                    'actor_user_id' => null,
                    'event_type' => 'lesson_resource.bytes_purged',
                    'auditable_type' => SchoolLessonResource::class,
                    'auditable_id' => $lockedResource->id,
                    'metadata' => ['resource_id' => $lockedResource->id],
                    'occurred_at' => now(),
                ]);

                return '';
            }

            $lockedResource->forceFill(['status' => SchoolLessonResourceStatus::Purged])->save();

            return $lockedResource->storage_key;
        });

        if ($storageKey === null) {
            return false;
        }

        if ($storageKey === '') {
            return true;
        }

        $deleted = Storage::disk($resource->storage_disk)->delete($storageKey);
        if (! $deleted) {
            return false;
        }

        return DB::transaction(function () use ($resource): bool {
            $school = School::query()->whereKey($resource->school_id)->lockForUpdate()->first();
            if (! $school instanceof School) {
                return false;
            }

            $lockedResource = $school->lessonResources()
                ->whereKey($resource->id)
                ->lockForUpdate()
                ->first();
            if (! $lockedResource instanceof SchoolLessonResource || $lockedResource->bytes_purged_at !== null) {
                return false;
            }

            $lockedResource->forceFill([
                'storage_key' => null,
                'bytes_purged_at' => now(),
            ])->save();

            $school->auditEvents()->create([
                'actor_user_id' => null,
                'event_type' => 'lesson_resource.bytes_purged',
                'auditable_type' => SchoolLessonResource::class,
                'auditable_id' => $lockedResource->id,
                'metadata' => ['resource_id' => $lockedResource->id],
                'occurred_at' => now(),
            ]);

            return true;
        });
    }
}
