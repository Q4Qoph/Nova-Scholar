<?php

namespace App\Services\Schools;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreateTerm
{
    /**
     * @param  array{name: string, starts_on: string, ends_on: string}  $data
     */
    public function handle(User $actor, School $school, AcademicYear $academicYear, array $data): Term
    {
        if ($academicYear->school_id !== $school->id) {
            throw new AuthorizationException('The academic year does not belong to this school.');
        }

        return DB::transaction(function () use ($actor, $school, $academicYear, $data): Term {
            $term = $academicYear->terms()->create([
                'school_id' => $school->id,
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => 'open',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'term.created',
                'auditable_type' => Term::class,
                'auditable_id' => $term->id,
                'metadata' => ['academic_year_id' => $academicYear->id, 'name' => $term->name],
                'occurred_at' => now(),
            ]);

            return $term;
        });
    }
}
