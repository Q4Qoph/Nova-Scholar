<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\StoreClassGroupRequest;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\StoreTeachingAssignmentRequest;
use App\Http\Requests\StoreTermRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\SchoolRole;
use App\Services\Schools\CreateAcademicYear;
use App\Services\Schools\CreateClassGroup;
use App\Services\Schools\CreateSubject;
use App\Services\Schools\CreateTeachingAssignment;
use App\Services\Schools\CreateTerm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SchoolAcademicController extends Controller
{
    public function index(School $school): View
    {
        Gate::authorize('viewAny', [AcademicYear::class, $school]);

        return view('schools.academic.index', [
            'school' => $school,
            'canManage' => Gate::allows('create', [AcademicYear::class, $school]),
            'academicYears' => $school->academicYears()->with(['terms', 'classGroups'])->latest('starts_on')->get(),
            'subjects' => $school->subjects()->latest('name')->get(),
            'assignments' => $school->teachingAssignments()->with(['classGroup', 'subject', 'teacher'])->latest('id')->get(),
            'teachers' => $school->memberships()
                ->active()
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::Teacher->value))
                ->with('user')
                ->get(),
        ]);
    }

    public function storeYear(StoreAcademicYearRequest $request, School $school, CreateAcademicYear $createAcademicYear): RedirectResponse
    {
        $createAcademicYear->handle($request->user(), $school, $request->validated());

        return to_route('schools.academic.index', $school)->with('status', 'Academic year created.');
    }

    public function storeTerm(StoreTermRequest $request, School $school, AcademicYear $academicYear, CreateTerm $createTerm): RedirectResponse
    {
        abort_unless($academicYear->school_id === $school->id, 404);
        $createTerm->handle($request->user(), $school, $academicYear, $request->validated());

        return to_route('schools.academic.index', $school)->with('status', 'Term created.');
    }

    public function storeClassGroup(StoreClassGroupRequest $request, School $school, AcademicYear $academicYear, CreateClassGroup $createClassGroup): RedirectResponse
    {
        abort_unless($academicYear->school_id === $school->id, 404);
        $createClassGroup->handle($request->user(), $school, $academicYear, $request->validated());

        return to_route('schools.academic.index', $school)->with('status', 'Class group created.');
    }

    public function storeSubject(StoreSubjectRequest $request, School $school, CreateSubject $createSubject): RedirectResponse
    {
        $createSubject->handle($request->user(), $school, $request->validated());

        return to_route('schools.academic.index', $school)->with('status', 'Subject created.');
    }

    public function storeAssignment(StoreTeachingAssignmentRequest $request, School $school, CreateTeachingAssignment $createTeachingAssignment): RedirectResponse
    {
        $createTeachingAssignment->handle($request->user(), $school, $request->validated());

        return to_route('schools.academic.index', $school)->with('status', 'Teaching assignment created.');
    }
}
