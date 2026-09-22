<?php

namespace App\Services\Schools;

use App\Models\ClassGroup;
use App\Models\Enrolment;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoteLearner
{
    /**
     * @param  array{class_group_id: int, starts_on: string}  $data
     */
    public function handle(User $actor, School $school, Enrolment $enrolment, array $data): LearnerClassMembership
    {
        return DB::transaction(function () use ($actor, $school, $enrolment, $data): LearnerClassMembership {
            $lockedEnrolment = Enrolment::query()
                ->whereKey($enrolment->id)
                ->where('school_id', $school->id)
                ->where('status', 'active')
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

            $startsOn = CarbonImmutable::parse($data['starts_on']);
            $currentPlacement = LearnerClassMembership::query()
                ->where('enrolment_id', $lockedEnrolment->id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $startsOn)
                ->where(function ($query) use ($startsOn): void {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $startsOn);
                })
                ->lockForUpdate()
                ->first();

            if ($currentPlacement !== null && $currentPlacement->class_group_id === $classGroup->id) {
                throw ValidationException::withMessages(['class_group_id' => 'The learner is already in this class on the selected date.']);
            }

            if ($currentPlacement !== null) {
                $previousEnd = $startsOn->subDay();

                if ($currentPlacement->starts_on->greaterThan($previousEnd)) {
                    throw ValidationException::withMessages(['starts_on' => 'The promotion date must be after the current placement starts.']);
                }

                $currentPlacement->update(['ends_on' => $previousEnd]);
            }

            $membership = $school->learnerClassMemberships()->create([
                'enrolment_id' => $lockedEnrolment->id,
                'class_group_id' => $classGroup->id,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => null,
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'learner.promoted',
                'auditable_type' => LearnerClassMembership::class,
                'auditable_id' => $membership->id,
                'metadata' => [
                    'enrolment_id' => $lockedEnrolment->id,
                    'class_group_id' => $classGroup->id,
                    'starts_on' => $membership->starts_on->toDateString(),
                    'previous_membership_id' => $currentPlacement?->id,
                ],
                'occurred_at' => now(),
            ]);

            return $membership;
        });
    }
}
