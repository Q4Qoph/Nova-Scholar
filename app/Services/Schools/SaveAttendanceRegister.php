<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveAttendanceRegister
{
    /**
     * @param  array{attendance_session_id?: int|null, version?: int|null, class_group_id: int, teaching_assignment_id: int, session_date: string, entries?: array<int, array{enrolment_id: int, status: string, correction_reason?: string|null}>|null}  $data
     */
    public function handle(User $actor, School $school, array $data): AttendanceSession
    {
        return DB::transaction(function () use ($actor, $school, $data): AttendanceSession {
            $session = AttendanceSession::query()
                ->where('school_id', $school->id)
                ->where('class_group_id', $data['class_group_id'])
                ->where('teaching_assignment_id', $data['teaching_assignment_id'])
                ->whereDate('session_date', $data['session_date'])
                ->lockForUpdate()
                ->first();

            $sessionWasCreated = $session === null;
            if ($sessionWasCreated) {
                $session = $school->attendanceSessions()->create([
                    'class_group_id' => $data['class_group_id'],
                    'teaching_assignment_id' => $data['teaching_assignment_id'],
                    'session_date' => $data['session_date'],
                    'status' => 'open',
                    'version' => 0,
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
            } elseif (($data['version'] ?? null) !== $session->version) {
                throw ValidationException::withMessages([
                    'version' => 'This register changed in another session. Refresh before saving again.',
                ]);
            }

            $existingEntries = $session->entries()->get()->keyBy('enrolment_id');
            $eligibleEnrolmentIds = $session->classGroup->learnerClassMemberships()
                ->where('school_id', $school->id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $session->session_date)
                ->where(function ($query) use ($session): void {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $session->session_date);
                })
                ->whereHas('enrolment', fn ($query) => $query->where('school_id', $school->id)->where('status', 'active'))
                ->pluck('enrolment_id');

            $submittedEntries = collect($data['entries'] ?? [])->keyBy('enrolment_id');
            if ($submittedEntries->keys()->diff($eligibleEnrolmentIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'entries' => 'Every attendance entry must belong to an active learner in this class on the session date.',
                ]);
            }

            $corrections = [];
            if (! $sessionWasCreated) {
                foreach ($eligibleEnrolmentIds as $enrolmentId) {
                    $existingEntry = $existingEntries->get($enrolmentId);
                    $submittedEntry = $submittedEntries->get($enrolmentId);
                    $status = $submittedEntry['status'] ?? 'unmarked';
                    if ($existingEntry === null || $existingEntry->status === $status) {
                        continue;
                    }

                    $reason = trim((string) ($submittedEntry['correction_reason'] ?? ''));
                    if ($reason === '') {
                        throw ValidationException::withMessages([
                            "entries.{$enrolmentId}.correction_reason" => 'A reason is required when changing an attendance status.',
                        ]);
                    }

                    $corrections[$enrolmentId] = ['from_status' => $existingEntry->status, 'to_status' => $status, 'reason' => $reason];
                }
            }

            $nextVersion = $session->version + 1;
            foreach ($eligibleEnrolmentIds as $enrolmentId) {
                $status = $submittedEntries->get($enrolmentId)['status'] ?? 'unmarked';
                $entry = $session->entries()->updateOrCreate(
                    ['enrolment_id' => $enrolmentId],
                    [
                        'status' => $status,
                        'marked_at' => $status === 'unmarked' ? null : now(),
                        'marked_by_user_id' => $status === 'unmarked' ? null : $actor->id,
                    ],
                );

                if (isset($corrections[$enrolmentId])) {
                    $entry->corrections()->create([
                        ...$corrections[$enrolmentId],
                        'attendance_session_id' => $session->id,
                        'corrected_by_user_id' => $actor->id,
                        'session_version' => $nextVersion,
                        'corrected_at' => now(),
                    ]);
                }
            }

            $session->update([
                'version' => $nextVersion,
                'updated_by_user_id' => $actor->id,
            ]);

            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'attendance_register.saved',
                'auditable_type' => AttendanceSession::class,
                'auditable_id' => $session->id,
                'metadata' => ['version' => $session->version, 'correction_count' => count($corrections), 'session_date' => $session->session_date->toDateString()],
                'occurred_at' => now(),
            ]);

            return $session->fresh(['classGroup', 'teachingAssignment', 'entries.enrolment.learnerProfile']);
        });
    }
}
