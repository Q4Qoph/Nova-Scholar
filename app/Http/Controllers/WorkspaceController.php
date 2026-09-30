<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ResolveWorkspaceDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __invoke(Request $request, ResolveWorkspaceDestination $resolver): RedirectResponse|View
    {
        $user = $request->user();
        if ($user->isManagedLearner()) {
            abort_unless($user->isActiveManagedLearner(), 403, 'Your learner access is unavailable. Contact your school.');

            return to_route('learner.dashboard');
        }
        if (! $user->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }
        $destinations = $resolver->destinations($user);
        if (count($destinations) === 1) {
            return redirect($destinations[0]['url']);
        }
        if ($destinations === []) {
            return to_route('study');
        }

        return view('workspace', compact('destinations'));
    }

    public function study(): View
    {
        return view('dashboard', ['schoolMemberships' => collect()]);
    }
}
