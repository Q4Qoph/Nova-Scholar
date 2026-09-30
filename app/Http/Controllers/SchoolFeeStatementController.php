<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FeeStatementRequest;
use App\Models\Enrolment;
use App\Models\FeeSchedule;
use App\Models\School;
use App\Services\Schools\BuildSchoolFeeStatement;
use App\Services\Schools\DownloadSchoolFeeStatementCsv;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolFeeStatementController extends Controller
{
    public function show(FeeStatementRequest $request, School $school, Enrolment $enrolment, BuildSchoolFeeStatement $buildSchoolFeeStatement): View
    {
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);
        abort_unless($enrolment->school_id === $school->id, 404);

        return view('schools.fees.statement', [
            'statement' => $buildSchoolFeeStatement->handle($school, $enrolment, $request->validated('from'), $request->validated('to')),
            'schoolContext' => true,
            'csvRoute' => 'schools.fees.statements.export',
        ]);
    }

    public function export(
        FeeStatementRequest $request,
        School $school,
        Enrolment $enrolment,
        BuildSchoolFeeStatement $buildSchoolFeeStatement,
        DownloadSchoolFeeStatementCsv $downloadSchoolFeeStatementCsv,
    ): StreamedResponse {
        Gate::authorize('viewAny', [FeeSchedule::class, $school]);
        abort_unless($enrolment->school_id === $school->id, 404);
        $statement = $buildSchoolFeeStatement->handle($school, $enrolment, $request->validated('from'), $request->validated('to'));

        return $downloadSchoolFeeStatementCsv->handle($statement);
    }
}
