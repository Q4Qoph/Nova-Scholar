<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FeeStatementRequest;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Services\Schools\BuildSchoolFeeStatement;
use App\Services\Schools\DownloadSchoolFeeStatementCsv;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardianFeeStatementController extends Controller
{
    public function show(FeeStatementRequest $request, Enrolment $enrolment, BuildSchoolFeeStatement $buildSchoolFeeStatement): View
    {
        $link = $this->verifiedLink($request, $enrolment);

        return view('schools.fees.statement', [
            'statement' => $buildSchoolFeeStatement->handle($link->school, $enrolment, $request->validated('from'), $request->validated('to')),
            'schoolContext' => false,
            'csvRoute' => 'guardian.learners.statements.export',
        ]);
    }

    public function export(
        FeeStatementRequest $request,
        Enrolment $enrolment,
        BuildSchoolFeeStatement $buildSchoolFeeStatement,
        DownloadSchoolFeeStatementCsv $downloadSchoolFeeStatementCsv,
    ): StreamedResponse {
        $link = $this->verifiedLink($request, $enrolment);
        $statement = $buildSchoolFeeStatement->handle($link->school, $enrolment, $request->validated('from'), $request->validated('to'));

        return $downloadSchoolFeeStatementCsv->handle($statement);
    }

    private function verifiedLink(FeeStatementRequest $request, Enrolment $enrolment): GuardianLink
    {
        return GuardianLink::query()
            ->where('guardian_user_id', $request->user()->id)
            ->where('enrolment_id', $enrolment->id)
            ->where('school_id', $enrolment->school_id)
            ->where('status', 'active')
            ->whereNotNull('verified_at')
            ->whereNull('revoked_at')
            ->whereHas('school', fn ($query) => $query->where('status', 'active'))
            ->whereHas('enrolment', fn ($query) => $query->where('status', 'active'))
            ->with(['school', 'enrolment.learnerProfile'])
            ->firstOrFail();
    }
}
