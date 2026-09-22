<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SendSchoolAnnouncement
{
    public function handle(User $actor, School $school, Announcement $announcement): Announcement
    {
        return DB::transaction(function () use ($actor, $school, $announcement): Announcement {
            $announcement = Announcement::query()
                ->whereKey($announcement->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($announcement->status === 'sent') {
                return $announcement;
            }

            $guardianLinks = $school->guardianLinks()
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->whereHas('school', fn ($query) => $query->where('status', 'active'))
                ->whereHas('enrolment', fn ($query) => $query->where('status', 'active'))
                ->when($announcement->audience_type === 'class_guardians', function ($query) use ($announcement): void {
                    $query->whereHas('enrolment.classMemberships', function ($membershipQuery) use ($announcement): void {
                        $membershipQuery->where('class_group_id', $announcement->class_group_id)
                            ->where('status', 'active')
                            ->whereDate('starts_on', '<=', today())
                            ->where(fn ($dateQuery) => $dateQuery->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()));
                    });
                })
                ->select('guardian_user_id')
                ->distinct()
                ->pluck('guardian_user_id');

            $now = now();
            $deliveries = $guardianLinks->map(fn (int $guardianUserId): array => [
                'announcement_id' => $announcement->id,
                'school_id' => $school->id,
                'guardian_user_id' => $guardianUserId,
                'channel' => 'in_app',
                'status' => 'sent',
                'delivered_at' => $now,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            if ($deliveries !== []) {
                $announcement->deliveries()->upsert($deliveries, ['announcement_id', 'guardian_user_id', 'channel'], ['status', 'delivered_at', 'updated_at']);
            }

            $announcement->update(['status' => 'sent', 'sent_at' => $now]);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'announcement.sent',
                'auditable_type' => Announcement::class,
                'auditable_id' => $announcement->id,
                'metadata' => ['delivery_count' => count($deliveries), 'audience_type' => $announcement->audience_type],
                'occurred_at' => $now,
            ]);

            return $announcement->fresh();
        });
    }
}
