<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolMembership;
use App\SchoolRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolController extends Controller
{
    public function overview(Request $request, School $school): View
    {
        Gate::authorize('view', $school);

        $membership = $request->attributes->get('school_membership');
        abort_unless($membership instanceof SchoolMembership, 404);

        return view('schools.overview', [
            'school' => $school,
            'membership' => $membership,
            'isSchoolAdmin' => $membership->roles->contains('role', SchoolRole::SchoolAdmin),
            'memberships' => $school->memberships()->active()->with(['user', 'roles'])->get(),
            'invitations' => $school->invitations()->whereNull('accepted_at')->whereNull('revoked_at')->with('invitee')->latest()->get(),
        ]);
    }
}
