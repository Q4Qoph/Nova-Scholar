<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeactivateLearnerRequest;
use App\Http\Requests\PromoteLearnerRequest;
use App\Http\Requests\StoreLearnerClassMembershipRequest;
use App\Http\Requests\StoreLearnerRequest;
use App\Http\Requests\TransferLearnerRequest;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\ImportBatch;
use App\Models\LearnerClassMembership;
use App\Models\School;
use App\SchoolRole;
use App\Services\Schools\AdmitLearner;
use App\Services\Schools\AssignLearnerClass;
use App\Services\Schools\DeactivateLearner;
use App\Services\Schools\PromoteLearner;
use App\Services\Schools\TransferLearner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolLearnerController extends Controller
{
    public function index(School $school): View
    {
        Gate::authorize('viewAny', [Enrolment::class, $school]);

        return view('schools.learners.index', [
            'school' => $school,
            'canAdmit' => Gate::allows('create', [Enrolment::class, $school]),
            'learners' => $school->enrolments()
                ->with('learnerProfile')
                ->latest('id')
                ->get(),
            'canImport' => Gate::allows('create', [ImportBatch::class, $school]),
            'importBatches' => $school->importBatches()->latest('id')->limit(5)->get(),
        ]);
    }

    public function store(StoreLearnerRequest $request, School $school, AdmitLearner $admitLearner): RedirectResponse
    {
        $admitLearner->handle($request->user(), $school, $request->validated());

        return to_route('schools.learners.index', $school)->with('status', 'Learner admitted.');
    }

    public function show(Request $request, School $school, Enrolment $learner): View
    {
        Gate::authorize('view', $learner);

        return view('schools.learners.show', [
            'school' => $school,
            'canManageGuardians' => Gate::allows('create', [GuardianLink::class, $school, $learner]),
            'canAssignClass' => Gate::allows('create', [LearnerClassMembership::class, $school]),
            'canManageAccess' => Gate::allows('create', [Enrolment::class, $school]),
            'canManageLifecycle' => Gate::allows('create', [Enrolment::class, $school]),
            'transferSchools' => $request->user()->schoolMemberships()
                ->active()
                ->whereHas('roles', fn ($query) => $query->where('role', SchoolRole::SchoolAdmin->value))
                ->whereHas('school', fn ($query) => $query->where('status', 'active')->where('id', '!=', $school->id))
                ->with('school')
                ->get()
                ->pluck('school'),
            'guardianLinks' => $learner->guardianLinks()->with('guardian')->where('status', 'active')->get(),
            'classMemberships' => $learner->classMemberships()->with('classGroup.academicYear')->latest('starts_on')->get(),
            'academicYears' => $school->academicYears()->with('classGroups')->latest('starts_on')->get(),
            'learner' => $learner->load('learnerProfile.user'),
        ]);
    }

    public function storeClassMembership(StoreLearnerClassMembershipRequest $request, School $school, Enrolment $learner, AssignLearnerClass $assignLearnerClass): RedirectResponse
    {
        $assignLearnerClass->handle($request->user(), $school, $learner, $request->validated());

        return to_route('schools.learners.show', [$school, $learner])->with('status', 'Learner class placement created.');
    }

    public function promote(PromoteLearnerRequest $request, School $school, Enrolment $learner, PromoteLearner $promoteLearner): RedirectResponse
    {
        $promoteLearner->handle($request->user(), $school, $learner, $request->validated());

        return to_route('schools.learners.show', [$school, $learner])->with('status', 'Learner promoted to the new class.');
    }

    public function transfer(TransferLearnerRequest $request, School $school, Enrolment $learner, TransferLearner $transferLearner): RedirectResponse
    {
        $destination = $transferLearner->handle($request->user(), $school, $learner, $request->validated());

        return to_route('schools.learners.show', [$school, $learner])->with('status', 'Learner transferred. New destination enrolment: '.$destination->admission_number.'.');
    }

    public function deactivate(DeactivateLearnerRequest $request, School $school, Enrolment $learner, DeactivateLearner $deactivateLearner): RedirectResponse
    {
        $deactivateLearner->handle($request->user(), $school, $learner, $request->validated('deactivated_on'));

        return to_route('schools.learners.show', [$school, $learner])->with('status', 'Learner enrolment and managed access deactivated.');
    }
}
