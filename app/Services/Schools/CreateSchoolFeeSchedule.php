<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\ClassGroup;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateSchoolFeeSchedule
{
    /**
     * @param  array{name: string, currency: string, amount_minor: int|string, term_id?: int|string|null, class_group_id?: int|string|null, starts_on?: string|null, ends_on?: string|null}  $data
     */
    public function handle(User $actor, School $school, array $data): FeeSchedule
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'amount_minor' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'term_id' => ['nullable', 'integer'],
            'class_group_id' => ['nullable', 'integer'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ])->validate();

        return DB::transaction(function () use ($actor, $school, $validated): FeeSchedule {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [FeeSchedule::class, $school]);

            $term = null;
            if (filled($validated['term_id'] ?? null)) {
                $term = $school->terms()
                    ->whereKey($validated['term_id'])
                    ->where('terms.status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (! $term instanceof Term) {
                    throw ValidationException::withMessages(['term_id' => 'Select an open term in this school.']);
                }
            }

            $classGroup = null;
            if (filled($validated['class_group_id'] ?? null)) {
                $classGroup = $school->classGroups()
                    ->whereKey($validated['class_group_id'])
                    ->where('class_groups.status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (! $classGroup instanceof ClassGroup) {
                    throw ValidationException::withMessages(['class_group_id' => 'Select an active class in this school.']);
                }
            }

            if ($term !== null && $classGroup !== null && $term->academic_year_id !== $classGroup->academic_year_id) {
                throw ValidationException::withMessages(['class_group_id' => 'The class must belong to the selected term academic year.']);
            }

            $schedule = $school->feeSchedules()->create([
                'term_id' => $term?->id,
                'class_group_id' => $classGroup?->id,
                'name' => trim($validated['name']),
                'currency' => strtoupper($validated['currency']),
                'amount_minor' => (int) $validated['amount_minor'],
                'starts_on' => $validated['starts_on'] ?? null,
                'ends_on' => $validated['ends_on'] ?? null,
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'fee_schedule.created',
                'auditable_type' => FeeSchedule::class,
                'auditable_id' => $schedule->id,
                'metadata' => [
                    'currency' => $schedule->currency,
                    'amount_minor' => $schedule->amount_minor,
                    'term_id' => $schedule->term_id,
                    'class_group_id' => $schedule->class_group_id,
                ],
                'occurred_at' => now(),
            ]);

            return $schedule;
        }, attempts: 3);
    }
}
