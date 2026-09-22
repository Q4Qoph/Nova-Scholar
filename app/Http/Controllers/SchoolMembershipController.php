<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRoleRequest;
use App\Models\School;
use App\Models\SchoolMembership;
use App\SchoolRole;
use App\Services\Schools\ManageSchoolRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolMembershipController extends Controller
{
    public function storeRole(StoreSchoolRoleRequest $request, School $school, SchoolMembership $membership, ManageSchoolRole $manage): RedirectResponse
    {
        abort_unless($membership->school_id === $school->id, 404);
        $manage->assign($request->user(), $membership, SchoolRole::from($request->string('role')->toString()));

        return back()->with('status', 'School role assigned.');
    }

    public function destroyRole(Request $request, School $school, SchoolMembership $membership, string $role, ManageSchoolRole $manage): RedirectResponse
    {
        abort_unless($membership->school_id === $school->id, 404);
        $schoolRole = SchoolRole::tryFrom($role);
        abort_unless($schoolRole?->isStaffRole(), 404);
        $manage->remove($request->user(), $membership, $schoolRole);

        return back()->with('status', 'School role removed.');
    }

    public function destroy(Request $request, School $school, SchoolMembership $membership, ManageSchoolRole $manage): RedirectResponse
    {
        abort_unless($membership->school_id === $school->id, 404);
        $manage->removeMembership($request->user(), $membership);

        return back()->with('status', 'School membership removed.');
    }
}
