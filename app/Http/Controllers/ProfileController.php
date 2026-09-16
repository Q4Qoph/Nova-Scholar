<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $existingPreferences = $user->learning_preferences ?? [];

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'learning_preferences' => [
                'preferred_subjects' => $validated['preferred_subjects'] ?? $existingPreferences['preferred_subjects'] ?? [],
                'daily_study_goal_minutes' => $validated['daily_study_goal_minutes'] ?? $existingPreferences['daily_study_goal_minutes'] ?? 60,
                'timezone' => $validated['timezone'] ?? $existingPreferences['timezone'] ?? 'Africa/Nairobi',
            ],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')->store('profile-photos');
            $previousPhotoPath = $user->profile_photo_path;
            $user->profile_photo_path = $newPhotoPath;
        }

        $user->save();

        if (isset($previousPhotoPath) && $previousPhotoPath !== null) {
            Storage::delete($previousPhotoPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->profile_photo_path !== null) {
            Storage::delete($user->profile_photo_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
