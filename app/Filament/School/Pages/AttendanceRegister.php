<?php

declare(strict_types=1);

namespace App\Filament\School\Pages;

use App\Models\AttendanceEntry;
use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\TeachingAssignment;
use App\SchoolRole;
use App\Services\Schools\SaveAttendanceRegister;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceRegister extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'School workspace';

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationLabel = 'Attendance';

    protected static ?string $title = 'Attendance register';

    protected static ?string $slug = 'attendance-register';

    protected string $view = 'filament.school.pages.attendance-register';

    public ?int $teachingAssignmentId = null;

    public string $sessionDate = '';

    public ?int $attendanceSessionId = null;

    public ?int $version = null;

    public bool $registerReady = false;

    /** @var array<int, array{status: string, correction_reason: string}> */
    public array $entries = [];

    /** @var array<int, string> */
    public array $learnerNames = [];

    public function mount(): void
    {
        $this->sessionDate = now()->toDateString();
    }

    public static function canAccess(): bool
    {
        $school = Filament::getTenant();

        return $school instanceof School
            && Gate::allows('viewAny', [AttendanceSession::class, $school]);
    }

    public function getSchool(): School
    {
        $school = Filament::getTenant();

        abort_unless($school instanceof School, 404);

        return $school;
    }

    public function updatedTeachingAssignmentId(): void
    {
        $this->resetRegister();
    }

    public function updatedSessionDate(): void
    {
        $this->resetRegister();
    }

    /**
     * @return Collection<int, TeachingAssignment>
     */
    public function getAssignments(): Collection
    {
        $user = auth()->user();

        return $this->getSchool()->teachingAssignments()
            ->where('status', 'active')
            ->whereHas('classGroup', fn ($query) => $query->where('status', 'active'))
            ->when(
                ! $user->schoolMemberships()
                    ->active()
                    ->where('school_id', $this->getSchool()->id)
                    ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                    ->exists(),
                fn ($query) => $query->where('teacher_user_id', $user->id),
            )
            ->with(['classGroup', 'subject'])
            ->orderBy('class_group_id')
            ->orderBy('subject_id')
            ->get();
    }

    /** @return array<int, string> */
    public function getRegisterRoster(): array
    {
        return $this->learnerNames;
    }

    public function openRegister(): void
    {
        $school = $this->getSchool();

        Gate::authorize('create', [AttendanceSession::class, $school]);

        $validated = $this->validate([
            'teachingAssignmentId' => [
                'required',
                'integer',
                Rule::exists('teaching_assignments', 'id')
                    ->where(fn ($query) => $query->where('school_id', $school->id)->where('status', 'active')),
            ],
            'sessionDate' => ['required', 'date'],
        ]);

        $assignment = $this->getAssignments()->firstWhere('id', $validated['teachingAssignmentId']);
        if (! $assignment instanceof TeachingAssignment) {
            throw ValidationException::withMessages([
                'teachingAssignmentId' => 'Choose an active class assignment available to your account.',
            ]);
        }

        $session = $school->attendanceSessions()
            ->where('class_group_id', $assignment->class_group_id)
            ->where('teaching_assignment_id', $assignment->id)
            ->whereDate('session_date', $validated['sessionDate'])
            ->first();

        if ($session !== null) {
            Gate::authorize('view', $session);
            $this->loadSession($session);
        } else {
            $this->prepareNewRegister($assignment, $validated['sessionDate']);
        }

        Notification::make()
            ->success()
            ->title('Attendance register opened')
            ->body('Learners without a submitted status remain unmarked.')
            ->send();
    }

    public function saveRegister(SaveAttendanceRegister $saveAttendanceRegister): void
    {
        $session = $this->getAttendanceSession();
        if ($session instanceof AttendanceSession) {
            Gate::authorize('update', $session);
        } else {
            Gate::authorize('create', [AttendanceSession::class, $this->getSchool()]);
        }

        $validated = $this->validate([
            'teachingAssignmentId' => [
                'required',
                'integer',
                Rule::exists('teaching_assignments', 'id')
                    ->where(fn ($query) => $query->where('school_id', $this->getSchool()->id)->where('status', 'active')),
            ],
            'sessionDate' => ['required', 'date'],
            'version' => ['nullable', 'integer', 'min:0'],
            'entries' => ['array'],
            'entries.*.status' => ['required', Rule::in(['unmarked', 'present', 'absent', 'late', 'excused'])],
            'entries.*.correction_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $entries = [];
        foreach ($validated['entries'] as $enrolmentId => $entry) {
            $entries[] = [
                'enrolment_id' => (int) $enrolmentId,
                'status' => $entry['status'],
                'correction_reason' => $entry['correction_reason'] ?? null,
            ];
        }

        if ($session instanceof AttendanceSession && $validated['version'] === null) {
            throw ValidationException::withMessages([
                'version' => 'Refresh this register before saving changes.',
            ]);
        }

        if ($session instanceof AttendanceSession && (int) $validated['version'] !== (int) $session->version) {
            throw ValidationException::withMessages([
                'version' => 'This register changed in another session. Refresh before saving again.',
            ]);
        }

        $assignment = $session?->teachingAssignment
            ?? $this->getAssignments()->firstWhere('id', $validated['teachingAssignmentId']);
        if (! $assignment instanceof TeachingAssignment) {
            throw ValidationException::withMessages([
                'teachingAssignmentId' => 'Choose an active class assignment available to your account.',
            ]);
        }

        $savedSession = $saveAttendanceRegister->handle(auth()->user(), $this->getSchool(), [
            'attendance_session_id' => $session?->id,
            'version' => $validated['version'],
            'class_group_id' => $assignment->class_group_id,
            'teaching_assignment_id' => $assignment->id,
            'session_date' => $session?->session_date->toDateString() ?? $validated['sessionDate'],
            'entries' => $entries,
        ]);

        $this->loadSession($savedSession);

        Notification::make()
            ->success()
            ->title('Attendance register saved')
            ->body('Changes and any correction reasons have been recorded.')
            ->send();
    }

    public function getAttendanceSession(): ?AttendanceSession
    {
        if ($this->attendanceSessionId === null) {
            return null;
        }

        $session = $this->getSchool()->attendanceSessions()
            ->with(['classGroup', 'teachingAssignment.subject', 'entries.enrolment.learnerProfile'])
            ->whereKey($this->attendanceSessionId)
            ->firstOrFail();

        Gate::authorize('view', $session);

        return $session;
    }

    private function loadSession(AttendanceSession $session): void
    {
        $this->entries = [];
        $this->learnerNames = [];
        $this->attendanceSessionId = $session->id;
        $this->teachingAssignmentId = $session->teaching_assignment_id;
        $this->sessionDate = $session->session_date->toDateString();
        $this->version = $session->version;
        $this->registerReady = true;
        $session->entries()
            ->with('enrolment.learnerProfile')
            ->orderBy('enrolment_id')
            ->get()
            ->each(function (AttendanceEntry $entry): void {
                $profile = $entry->enrolment->learnerProfile;
                $this->learnerNames[$entry->enrolment_id] = trim(($profile->preferred_name ?: $profile->first_name).' '.$profile->last_name);
                $this->entries[$entry->enrolment_id] = [
                    'status' => $entry->status,
                    'correction_reason' => '',
                ];
            });

        ksort($this->learnerNames);
    }

    private function prepareNewRegister(TeachingAssignment $assignment, string $sessionDate): void
    {
        $this->attendanceSessionId = null;
        $this->version = null;
        $this->registerReady = true;
        $this->entries = [];
        $this->learnerNames = [];

        $assignment->classGroup->learnerClassMemberships()
            ->where('school_id', $this->getSchool()->id)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $sessionDate)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $sessionDate))
            ->whereHas('enrolment', fn ($query) => $query->where('school_id', $this->getSchool()->id)->where('status', 'active'))
            ->with('enrolment.learnerProfile')
            ->get()
            ->each(function ($membership): void {
                $enrolment = $membership->enrolment;
                $profile = $enrolment->learnerProfile;
                $this->learnerNames[$enrolment->id] = trim(($profile->preferred_name ?: $profile->first_name).' '.$profile->last_name);
                $this->entries[$enrolment->id] = [
                    'status' => 'unmarked',
                    'correction_reason' => '',
                ];
            });

        ksort($this->learnerNames);
    }

    private function resetRegister(): void
    {
        $this->attendanceSessionId = null;
        $this->version = null;
        $this->registerReady = false;
        $this->entries = [];
        $this->learnerNames = [];
    }
}
