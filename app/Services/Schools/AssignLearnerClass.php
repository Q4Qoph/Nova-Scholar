<?php

namespace App\Services\Schools;

use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignLearnerClass
{
    /**
     * @param  array{class_group_id: int, starts_on: string, ends_on?: string|null}  $data
     */
    public function handle(User $actor, School $school, Enrolment $enrolment, array $data): LearnerClassMembership
    {
        return DB::transaction(function () use ($actor, $school, $enrolment, $data): LearnerClassMembership {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedEnrolment = Enrolment::query()
                ->whereKey($enrolment->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->first();
            $classGroup = ClassGroup::query()
                ->whereKey($data['class_group_id'])
                ->where('school_id', $school->id)
                ->where('status', 'active')
                ->first();

            if ($lockedEnrolment === null || $classGroup === null) {
                throw new AuthorizationException('The learner and class must belong to the active school.');
            }

            $overlap = LearnerClassMembership::query()
                ->where('enrolment_id', $lockedEnrolment->id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $data['ends_on'] ?? '9999-12-31')
                ->where(function ($query) use ($data): void {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $data['starts_on']);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_on' => 'The learner already has a class placement during these dates.',
                ]);
            }

            $membership = $school->learnerClassMemberships()->create([
                'enrolment_id' => $lockedEnrolment->id,
                'class_group_id' => $classGroup->id,
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'learner_class_membership.created',
                'auditable_type' => LearnerClassMembership::class,
                'auditable_id' => $membership->id,
                'metadata' => [
                    'enrolment_id' => $lockedEnrolment->id,
                    'class_group_id' => $classGroup->id,
                    'starts_on' => $membership->starts_on->toDateString(),
                    'ends_on' => $membership->ends_on?->toDateString(),
                ],
                'occurred_at' => now(),
            ]);

            return $membership;
        });
    }
}
