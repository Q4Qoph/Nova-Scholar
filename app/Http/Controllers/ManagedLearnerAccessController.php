<?php

namespace App\Http\Controllers;

use App\Models\Enrolment;
use App\Models\School;
use App\Services\Schools\CreateManagedLearnerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ManagedLearnerAccessController extends Controller
{
    public function store(Request $request, School $school, Enrolment $learner, CreateManagedLearnerAccess $createManagedLearnerAccess): RedirectResponse
    {
        Gate::authorize('create', [Enrolment::class, $school]);
        $result = $createManagedLearnerAccess->handle($request->user(), $school, $learner);

        return to_route('schools.learners.show', [$school, $learner])
            ->with('status', 'Managed learner access issued.')
            ->with('activation_url', route('learner.activate.show', $result['token']));
    }
}
