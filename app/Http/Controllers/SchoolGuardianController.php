<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuardianLinkRequest;
use App\Models\Enrolment;
use App\Models\GuardianLink;
use App\Models\School;
use App\Services\Schools\LinkGuardian;
use App\Services\Schools\RevokeGuardianLink;
use Illuminate\Http\RedirectResponse;

class SchoolGuardianController extends Controller
{
    public function store(StoreGuardianLinkRequest $request, School $school, Enrolment $learner, LinkGuardian $linkGuardian): RedirectResponse
    {
        $linkGuardian->handle(
            $request->user(),
            $school,
            $learner,
            $request->string('email')->toString(),
            $request->string('relationship')->toString(),
        );

        return back()->with('status', 'Guardian relationship verified.');
    }

    public function destroy(School $school, Enrolment $learner, GuardianLink $guardianLink, RevokeGuardianLink $revokeGuardianLink): RedirectResponse
    {
        abort_unless($guardianLink->school_id === $school->id && $guardianLink->enrolment_id === $learner->id, 404);

        $revokeGuardianLink->handle(request()->user(), $guardianLink);

        return back()->with('status', 'Guardian relationship revoked.');
    }
}
