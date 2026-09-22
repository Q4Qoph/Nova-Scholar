<?php

namespace App\Services\Schools;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateClassGroup
{
    /**
     * @param  array{name: string, grade_level: string, stream?: string|null}  $data
     */
    public function handle(User $actor, School $school, AcademicYear $academicYear, array $data): ClassGroup
    {
        return DB::transaction(function () use ($actor, $school, $academicYear, $data): ClassGroup {
            $classGroup = $academicYear->classGroups()->create([
                'school_id' => $school->id,
                'name' => $data['name'],
                'grade_level' => $data['grade_level'],
                'stream' => $data['stream'] ?? null,
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'class_group.created',
                'auditable_type' => ClassGroup::class,
                'auditable_id' => $classGroup->id,
                'metadata' => ['academic_year_id' => $academicYear->id, 'name' => $classGroup->name],
                'occurred_at' => now(),
            ]);

            return $classGroup;
        });
    }
}
