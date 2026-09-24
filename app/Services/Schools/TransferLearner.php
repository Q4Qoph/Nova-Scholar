<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\School;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransferLearner
{
    /**
     * @param  array{destination_school_id: int, admission_number: string, transferred_on: string}  $data
     */
    public function handle(User $actor, School $sourceSchool, Enrolment $enrolment, array $data): Enrolment
    {
        return DB::transaction(function () use ($actor, $sourceSchool, $enrolment, $data): Enrolment {
            $schools = School::query()
                ->whereIn('id', [$sourceSchool->id, $data['destination_school_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $sourceSchool = $schools->get($sourceSchool->id);
            $destinationSchool = $schools->get($data['destination_school_id']);

            if ($sourceSchool === null || $destinationSchool === null || $destinationSchool->status !== 'active' || $destinationSchool->is($sourceSchool)) {
                throw new AuthorizationException('The source enrolment and destination school are invalid.');
            }

            $sourceEnrolment = Enrolment::query()
                ->whereKey($enrolment->id)
                ->where('school_id', $sourceSchool->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($sourceEnrolment === null) {
                throw new AuthorizationException('The source enrolment and destination school are invalid.');
            }

            Gate::forUser($actor)->authorize('create', [Enrolment::class, $destinationSchool]);

            $transferredOn = CarbonImmutable::parse($data['transferred_on']);
            if (Enrolment::query()->where('school_id', $destinationSchool->id)->where('learner_profile_id', $sourceEnrolment->learner_profile_id)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['destination_school_id' => 'This learner already has an active enrolment at the destination school.']);
            }

            $destinationEnrolment = $destinationSchool->enrolments()->create([
                'learner_profile_id' => $sourceEnrolment->learner_profile_id,
                'admission_number' => $data['admission_number'],
                'status' => 'active',
                'enrolled_at' => $transferredOn->toDateString(),
            ]);
            $sourceEnrolment->update(['status' => 'withdrawn', 'withdrawn_at' => $transferredOn->toDateString()]);
            $sourceEnrolment->classMemberships()
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $transferredOn)
                ->where(function ($query) use ($transferredOn): void {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $transferredOn);
                })
                ->update(['ends_on' => $transferredOn->subDay()->toDateString()]);

            foreach ([[$sourceSchool, 'learner.transferred_out', $sourceEnrolment, ['destination_school_id' => $destinationSchool->id, 'destination_enrolment_id' => $destinationEnrolment->id]], [$destinationSchool, 'learner.transferred_in', $destinationEnrolment, ['source_school_id' => $sourceSchool->id, 'source_enrolment_id' => $sourceEnrolment->id]]] as [$auditSchool, $eventType, $auditEnrolment, $metadata]) {
                $auditSchool->auditEvents()->create([
                    'actor_user_id' => $actor->id,
                    'event_type' => $eventType,
                    'auditable_type' => Enrolment::class,
                    'auditable_id' => $auditEnrolment->id,
                    'metadata' => [...$metadata, 'transferred_on' => $transferredOn->toDateString()],
                    'occurred_at' => now(),
                ]);
            }

            return $destinationEnrolment;
        });
    }
}
