<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\DeveloperFeatures;
use App\Support\ScanNoise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
        unset($validated['features'], $validated['scan_noise']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $preferences = $user->preferences ?? [];

        if ($request->exists('scan_noise')) {
            $preferences = ScanNoise::mergeIntoPreferences(
                $preferences,
                $request->boolean('scan_noise'),
            );
        }

        if ($user->hasRole('admin')) {
            $featureFlags = [];
            foreach (DeveloperFeatures::keys() as $key) {
                $featureFlags[$key] = $request->boolean('features.'.$key);
            }

            $preferences = DeveloperFeatures::mergeIntoPreferences(
                $preferences,
                $featureFlags,
            );
        }

        $user->preferences = $preferences;
        $user->save();

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

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
