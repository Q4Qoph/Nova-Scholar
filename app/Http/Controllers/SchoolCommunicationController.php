<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\School;
use App\Services\Schools\CreateSchoolAnnouncement;
use App\Services\Schools\SendSchoolAnnouncement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolCommunicationController extends Controller
{
    public function index(School $school): View
    {
        Gate::authorize('viewAny', [Announcement::class, $school]);

        return view('schools.communication.index', [
            'school' => $school,
            'announcements' => $school->announcements()->with('classGroup')->latest('id')->get(),
            'classGroups' => $school->classGroups()
                ->where('class_groups.status', 'active')
                ->orderBy('class_groups.name')
                ->get(),
        ]);
    }

    public function store(CreateAnnouncementRequest $request, School $school, CreateSchoolAnnouncement $createSchoolAnnouncement): RedirectResponse
    {
        $createSchoolAnnouncement->handle($request->user(), $school, $request->validated());

        return to_route('schools.communication.index', $school)->with('status', 'Notice saved as a draft.');
    }

    public function send(Request $request, School $school, Announcement $announcement, SendSchoolAnnouncement $sendSchoolAnnouncement): RedirectResponse
    {
        abort_unless($announcement->school_id === $school->id, 404);
        Gate::authorize('send', $announcement);
        $sendSchoolAnnouncement->handle($request->user(), $school, $announcement);

        return to_route('schools.communication.index', $school)->with('status', 'Notice sent in the guardian portal.');
    }
}
