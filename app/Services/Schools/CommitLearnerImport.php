<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\ImportBatch;
use App\Models\LearnerProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommitLearnerImport
{
    public function handle(User $actor, School $school, ImportBatch $batch): ImportBatch
    {
        return DB::transaction(function () use ($actor, $school, $batch): ImportBatch {
            $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();
            $lockedBatch = ImportBatch::query()
                ->whereKey($batch->id)
                ->where('school_id', $school->id)
                ->lockForUpdate()
                ->firstOrFail();

            $committedCount = 0;

            foreach ($lockedBatch->rows()->where('status', 'valid')->orderBy('row_number')->get() as $row) {
                if ($row->committed_enrolment_id !== null) {
                    continue;
                }

                $payload = $row->payload;
                $existingEnrolment = Enrolment::query()
                    ->where('school_id', $school->id)
                    ->where('admission_number', $payload['admission_number'])
                    ->first();

                if ($existingEnrolment !== null) {
                    $row->update([
                        'status' => 'invalid',
                        'validation_errors' => ['admission_number' => ['This admission number already exists in the school.']],
                    ]);

                    continue;
                }

                $profile = LearnerProfile::query()->create([
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'preferred_name' => $payload['preferred_name'] ?: null,
                    'date_of_birth' => $payload['date_of_birth'] ?: null,
                    'status' => 'active',
                ]);

                $enrolment = $school->enrolments()->create([
                    'learner_profile_id' => $profile->id,
                    'admission_number' => $payload['admission_number'],
                    'status' => 'active',
                    'enrolled_at' => today(),
                ]);

                $row->update([
                    'status' => 'committed',
                    'committed_enrolment_id' => $enrolment->id,
                ]);
                $committedCount++;
            }

            $invalidCount = $lockedBatch->rows()->where('status', 'invalid')->count();
            $status = $invalidCount > 0 ? ($committedCount > 0 ? 'partially_committed' : 'staged') : 'committed';

            $lockedBatch->update([
                'status' => $status,
                'valid_rows' => $lockedBatch->rows()->where('status', 'valid')->count(),
                'invalid_rows' => $invalidCount,
                'committed_at' => $committedCount > 0 ? now() : $lockedBatch->committed_at,
            ]);

            if ($committedCount > 0) {
                $school->auditEvents()->create([
                    'actor_user_id' => $actor->id,
                    'event_type' => 'learner_import.committed',
                    'auditable_type' => ImportBatch::class,
                    'auditable_id' => $lockedBatch->id,
                    'metadata' => [
                        'committed_rows' => $committedCount,
                        'invalid_rows' => $invalidCount,
                    ],
                    'occurred_at' => now(),
                ]);
            }

            return $lockedBatch->fresh();
        });
    }
}
