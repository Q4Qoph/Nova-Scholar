<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSubject
{
    /**
     * @param  array{name: string, code: string}  $data
     */
    public function handle(User $actor, School $school, array $data): Subject
    {
        return DB::transaction(function () use ($actor, $school, $data): Subject {
            $subject = $school->subjects()->create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'status' => 'active',
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'subject.created',
                'auditable_type' => Subject::class,
                'auditable_id' => $subject->id,
                'metadata' => ['code' => $subject->code, 'name' => $subject->name],
                'occurred_at' => now(),
            ]);

            return $subject;
        });
    }
}
