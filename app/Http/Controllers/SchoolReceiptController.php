<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AllocateSchoolReceiptRequest;
use App\Http\Requests\StoreSchoolReceiptRequest;
use App\Models\School;
use App\Services\Schools\AllocateSchoolReceipt;
use App\Services\Schools\RecordSchoolReceipt;
use Illuminate\Http\RedirectResponse;

class SchoolReceiptController extends Controller
{
    public function store(StoreSchoolReceiptRequest $request, School $school, RecordSchoolReceipt $recordSchoolReceipt): RedirectResponse
    {
        $recordSchoolReceipt->handle($request->user(), $school, $request->validated());

        return to_route('schools.fees.index', $school)->with('status', 'Receipt recorded as manually confirmed.');
    }

    public function allocate(
        AllocateSchoolReceiptRequest $request,
        School $school,
        AllocateSchoolReceipt $allocateSchoolReceipt,
    ): RedirectResponse {
        $validated = $request->validated();
        $allocateSchoolReceipt->handle(
            $request->user(),
            $school,
            (int) $validated['school_receipt_id'],
            (int) $validated['fee_charge_id'],
            (int) $validated['amount_minor'],
            $validated['allocation_key'],
        );

        return to_route('schools.fees.index', $school)->with('status', 'Receipt amount allocated to the posted charge.');
    }
}
