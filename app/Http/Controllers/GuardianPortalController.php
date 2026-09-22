<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\GuardianLink;
use App\Models\MessageDelivery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GuardianPortalController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Collection<int, GuardianLink> $links */
        $links = GuardianLink::query()
            ->where('guardian_user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->whereHas('school', fn ($query) => $query->where('status', 'active'))
            ->whereHas('enrolment', fn ($query) => $query->where('status', 'active'))
            ->with(['school', 'enrolment.learnerProfile'])
            ->latest('id')
            ->get();

        $selectedLink = $links->first();
        if ($request->filled('learner')) {
            $selectedLink = $links->firstWhere('enrolment_id', $request->integer('learner'));
            abort_unless($selectedLink instanceof GuardianLink, 404);
        }

        $attendanceSessions = collect();
        $notices = MessageDelivery::query()
            ->where('guardian_user_id', $request->user()->id)
            ->where('status', 'sent')
            ->whereIn('school_id', $links->pluck('school_id'))
            ->with('announcement.school')
            ->latest('delivered_at')
            ->get();
        if ($selectedLink instanceof GuardianLink) {
            $attendanceSessions = AttendanceSession::query()
                ->where('school_id', $selectedLink->school_id)
                ->whereHas('entries', fn ($query) => $query->where('enrolment_id', $selectedLink->enrolment_id))
                ->with([
                    'classGroup',
                    'teachingAssignment.subject',
                    'entries' => fn ($query) => $query->where('enrolment_id', $selectedLink->enrolment_id),
                ])
                ->latest('session_date')
                ->latest('id')
                ->get();
        }

        return view('guardian.learners', compact('links', 'selectedLink', 'attendanceSessions', 'notices'));
    }
}
