<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFeeScheduleRequest;
use App\Http\Requests\PostFeeChargeBatchRequest;
use App\Models\FeeCharge;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Services\Schools\CreateSchoolFeeSchedule;
use App\Services\Schools\PostFeeChargeBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SchoolFeeController extends Controller
{
    public function index(School $school): View
    {
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);

        return view('schools.fees.index', $this->viewData($school));
    }

    public function storeSchedule(CreateFeeScheduleRequest $request, School $school, CreateSchoolFeeSchedule $createSchoolFeeSchedule): RedirectResponse
    {
        $createSchoolFeeSchedule->handle($request->user(), $school, $request->validated());

        return to_route('schools.fees.index', $school)->with('status', 'Fee schedule saved.');
    }

    public function preview(PostFeeChargeBatchRequest $request, School $school, PostFeeChargeBatch $postFeeChargeBatch): View
    {
        $schedule = $school->feeSchedules()->findOrFail($request->integer('fee_schedule_id'));
        $batch = $postFeeChargeBatch->preview($request->user(), $school, $schedule, $request->string('batch_key')->toString());

        return view('schools.fees.index', [...$this->viewData($school), 'preview' => $batch]);
    }

    public function post(PostFeeChargeBatchRequest $request, School $school, PostFeeChargeBatch $postFeeChargeBatch): RedirectResponse
    {
        $schedule = $school->feeSchedules()->findOrFail($request->integer('fee_schedule_id'));
        $postFeeChargeBatch->handle($request->user(), $school, $schedule, $request->string('batch_key')->toString());

        return to_route('schools.fees.index', $school)->with('status', 'Fee charges posted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(School $school): array
    {
        $charges = $school->feeCharges()
            ->where('status', 'posted')
            ->with(['enrolment.learnerProfile'])
            ->withSum('receiptAllocations', 'amount_minor')
            ->latest('id')
            ->paginate(25, ['*'], 'chargePage');
        $receipts = $school->receipts()
            ->with('verifiedBy:id,name')
            ->withSum('allocations', 'amount_minor')
            ->latest('id')
            ->paginate(25, ['*'], 'receiptPage');

        return [
            'school' => $school,
            'feeSchedules' => $school->feeSchedules()->with(['term', 'classGroup'])->latest('id')->get(),
            'terms' => $school->terms()->where('terms.status', 'open')->latest('terms.starts_on')->get(),
            'classGroups' => $school->classGroups()->where('class_groups.status', 'active')->orderBy('class_groups.name')->get(),
            'charges' => $charges,
            'receipts' => $receipts,
            'receiptSubmissionKey' => old('submission_key', (string) Str::uuid()),
            'allocationKeys' => $charges->getCollection()->mapWithKeys(fn (FeeCharge $charge): array => [
                $charge->id => old('allocation_keys.'.$charge->id, (string) Str::uuid()),
            ]),
        ];
    }
}
