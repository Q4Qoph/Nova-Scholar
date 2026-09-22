<?php

namespace App\Services\Schools;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAcademicYear
{
    /**
     * @param  array{name: string, starts_on: string, ends_on: string}  $data
     */
    public function handle(User $actor, School $school, array $data): AcademicYear
    {
        return DB::transaction(function () use ($actor, $school, $data): AcademicYear {
            $academicYear = $school->academicYears()->create([
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => 'open',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'academic_year.created',
                'auditable_type' => AcademicYear::class,
                'auditable_id' => $academicYear->id,
                'metadata' => ['name' => $academicYear->name],
                'occurred_at' => now(),
            ]);

            return $academicYear;
        });
    }
}
