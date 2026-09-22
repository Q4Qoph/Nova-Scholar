<?php

namespace App\Http\Controllers;

use App\Http\Requests\LearnerLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LearnerSessionController extends Controller
{
    public function create(): View
    {
        return view('learner.login');
    }

    public function store(LearnerLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();
        Auth::login($user, false);
        $request->session()->regenerate();

        return to_route('learner.dashboard');
    }

    public function dashboard(Request $request): View
    {
        $learnerProfile = $request->user()->learnerProfile()->with(['enrolments.classMemberships.classGroup.academicYear'])->firstOrFail();

        return view('learner.dashboard', ['learnerProfile' => $learnerProfile]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('learner.login');
    }
}
