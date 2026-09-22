<?php

namespace App\Services\Schools;

use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\SchoolRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreateTeachingAssignment
{
    /**
     * @param  array{class_group_id: int, subject_id: int, teacher_user_id: int}  $data
     */
    public function handle(User $actor, School $school, array $data): TeachingAssignment
    {
        $classGroup = ClassGroup::query()->whereKey($data['class_group_id'])->where('school_id', $school->id)->first();
        $subject = Subject::query()->whereKey($data['subject_id'])->where('school_id', $school->id)->first();
        if (! $classGroup instanceof ClassGroup || ! $subject instanceof Subject) {
            throw new AuthorizationException('The class and subject must belong to this school.');
        }
        if (! SchoolMembership::query()
            ->active()
            ->where('school_id', $school->id)
            ->where('user_id', $data['teacher_user_id'])
            ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::Teacher->value))
            ->exists()) {
            throw new AuthorizationException('The assigned user must be an active teacher in this school.');
        }

        return DB::transaction(function () use ($actor, $school, $classGroup, $subject, $data): TeachingAssignment {
            $assignment = $school->teachingAssignments()->create([
                'class_group_id' => $classGroup->id,
                'subject_id' => $subject->id,
                'teacher_user_id' => $data['teacher_user_id'],
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'teaching_assignment.created',
                'auditable_type' => TeachingAssignment::class,
                'auditable_id' => $assignment->id,
                'metadata' => ['class_group_id' => $classGroup->id, 'subject_id' => $subject->id, 'teacher_user_id' => $data['teacher_user_id']],
                'occurred_at' => now(),
            ]);

            return $assignment;
        });
    }
}
