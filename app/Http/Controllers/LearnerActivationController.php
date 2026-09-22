<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateLearnerRequest;
use App\Models\LearnerActivation;
use App\Services\Schools\ActivateManagedLearner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LearnerActivationController extends Controller
{
    public function show(string $token): View
    {
        abort_unless(LearnerActivation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists(), 404);

        return view('learner.activate', ['token' => $token]);
    }

    public function store(ActivateLearnerRequest $request, ActivateManagedLearner $activateManagedLearner): RedirectResponse
    {
        $user = $activateManagedLearner->handle($request->validated('token'), $request->validated('password'));
        Auth::logout();
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('learner.dashboard');
    }
}
