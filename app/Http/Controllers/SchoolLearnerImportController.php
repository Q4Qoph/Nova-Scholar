<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearnerImportRequest;
use App\Models\ImportBatch;
use App\Models\School;
use App\Services\Schools\CommitLearnerImport;
use App\Services\Schools\StageLearnerImport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolLearnerImportController extends Controller
{
    public function show(School $school, ImportBatch $importBatch): View
    {
        Gate::authorize('view', $importBatch);

        return view('schools.learners.import', [
            'school' => $school,
            'batch' => $importBatch->load(['rows' => fn ($query) => $query->orderBy('row_number')]),
        ]);
    }

    public function store(StoreLearnerImportRequest $request, School $school, StageLearnerImport $stageLearnerImport): RedirectResponse
    {
        $batch = $stageLearnerImport->handle($request->user(), $school, $request->file('file'));

        return to_route('schools.learner-imports.show', [$school, $batch])->with('status', 'Learner CSV staged for review.');
    }

    public function commit(Request $request, School $school, ImportBatch $importBatch, CommitLearnerImport $commitLearnerImport): RedirectResponse
    {
        Gate::authorize('commit', $importBatch);
        $batch = $commitLearnerImport->handle($request->user(), $school, $importBatch);

        return to_route('schools.learner-imports.show', [$school, $batch])->with('status', 'Valid learner rows committed.');
    }
}
