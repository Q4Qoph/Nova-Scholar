<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFeeScheduleRequest;
use App\Http\Requests\PostFeeChargeBatchRequest;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Services\Schools\PostFeeChargeBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SchoolFeeController extends Controller
{
    public function index(School $school): View
    {
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);

        return view('schools.fees.index', [
            'school' => $school,
            'feeSchedules' => $school->feeSchedules()->with(['term', 'classGroup'])->latest('id')->get(),
            'terms' => $school->terms()->where('terms.status', 'active')->latest('terms.starts_on')->get(),
            'classGroups' => $school->classGroups()->where('class_groups.status', 'active')->orderBy('class_groups.name')->get(),
        ]);
    }

    public function storeSchedule(CreateFeeScheduleRequest $request, School $school): RedirectResponse
    {
        $school->feeSchedules()->create($request->validated());

        return to_route('schools.fees.index', $school)->with('status', 'Fee schedule saved.');
    }

    public function preview(PostFeeChargeBatchRequest $request, School $school, PostFeeChargeBatch $postFeeChargeBatch): View
    {
        $schedule = $school->feeSchedules()->findOrFail($request->integer('fee_schedule_id'));
        $batch = $postFeeChargeBatch->preview($request->user(), $school, $schedule, $request->string('batch_key')->toString());

        return view('schools.fees.index', [
            'school' => $school,
            'feeSchedules' => $school->feeSchedules()->with(['term', 'classGroup'])->latest('id')->get(),
            'terms' => $school->terms()->where('terms.status', 'active')->latest('terms.starts_on')->get(),
            'classGroups' => $school->classGroups()->where('class_groups.status', 'active')->orderBy('class_groups.name')->get(),
            'preview' => $batch,
        ]);
    }

    public function post(PostFeeChargeBatchRequest $request, School $school, PostFeeChargeBatch $postFeeChargeBatch): RedirectResponse
    {
        $schedule = $school->feeSchedules()->findOrFail($request->integer('fee_schedule_id'));
        $postFeeChargeBatch->handle($request->user(), $school, $schedule, $request->string('batch_key')->toString());

        return to_route('schools.fees.index', $school)->with('status', 'Fee charges posted.');
    }
}
