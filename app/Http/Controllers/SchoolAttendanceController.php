<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRegisterRequest;
use App\Models\AttendanceSession;
use App\Models\School;
use App\SchoolRole;
use App\Services\Schools\SaveAttendanceRegister;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolAttendanceController extends Controller
{
    public function index(Request $request, School $school): View
    {
        Gate::authorize('viewAny', [AttendanceSession::class, $school]);

        $membership = $request->attributes->get('school_membership');
        $isSchoolAdmin = $membership?->roles->contains('role', SchoolRole::SchoolAdmin) === true;
        $assignments = $school->teachingAssignments()
            ->where('status', 'active')
            ->when(! $isSchoolAdmin, fn ($query) => $query->where('teacher_user_id', $request->user()->id))
            ->with(['classGroup', 'subject'])
            ->orderBy('class_group_id')
            ->get();

        $selectedSession = null;
        if ($request->filled('session')) {
            $selectedSession = $school->attendanceSessions()
                ->with(['classGroup', 'teachingAssignment.subject', 'entries.enrolment.learnerProfile'])
                ->whereKey($request->integer('session'))
                ->firstOrFail();
            Gate::authorize('view', $selectedSession);
        }

        return view('schools.attendance.index', compact('school', 'assignments', 'selectedSession'));
    }

    public function store(StoreAttendanceRegisterRequest $request, School $school, SaveAttendanceRegister $saveAttendanceRegister): RedirectResponse
    {
        $session = $saveAttendanceRegister->handle($request->user(), $school, $request->validated());

        return to_route('schools.attendance.index', ['school' => $school, 'session' => $session->id])
            ->with('status', 'Attendance register saved.');
    }
}
