<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSchoolInvitationRequest;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\SchoolRole;
use App\Services\Schools\AcceptSchoolInvitation;
use App\Services\Schools\CreateSchoolInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolInvitationController extends Controller
{
    public function store(CreateSchoolInvitationRequest $request, School $school, CreateSchoolInvitation $create): RedirectResponse
    {
        $created = $create->handle(
            $request->user(),
            $school,
            $request->string('email')->toString(),
            SchoolRole::from($request->string('role')->toString()),
        );

        return to_route('schools.overview', $school)
            ->with('invitation_url', route('school-invitations.show', $created['token']));
    }

    public function show(Request $request, string $token): View
    {
        $invitation = SchoolInvitation::query()
            ->with('school')
            ->where('token_hash', hash('sha256', $token))
            ->first();
        if ($invitation === null || $invitation->accepted_at !== null || $invitation->revoked_at !== null || $invitation->expires_at->isPast() || $invitation->invitee_user_id !== $request->user()->id || $invitation->email !== $request->user()->email) {
            abort(404);
        }

        return view('schools.invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, string $token, AcceptSchoolInvitation $accept): RedirectResponse
    {
        $membership = $accept->handle($request->user(), $token);

        return to_route('schools.overview', $membership->load('school')->school)
            ->with('status', 'School invitation accepted.');
    }

    public function destroy(Request $request, School $school, SchoolInvitation $invitation, CreateSchoolInvitation $create): RedirectResponse
    {
        abort_unless($invitation->school_id === $school->id, 404);
        $create->revoke($request->user(), $invitation);

        return back()->with('status', 'Invitation revoked.');
    }
}
