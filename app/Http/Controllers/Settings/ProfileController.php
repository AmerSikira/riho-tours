<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'potpisUrl' => $user?->potpis_path
                ? Storage::disk('public')->url($user->potpis_path)
                : null,
            'pecatUrl' => $user?->pecat_path
                ? Storage::disk('public')->url($user->pecat_path)
                : null,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $potpisPath = $user->potpis_path;
        if ($request->hasFile('potpis')) {
            if ($potpisPath) {
                Storage::disk('public')->delete($potpisPath);
            }

            $potpisPath = $request->file('potpis')?->store('users/signatures', 'public');
        }

        $pecatPath = $user->pecat_path;
        if ($request->hasFile('pecat')) {
            if ($pecatPath) {
                Storage::disk('public')->delete($pecatPath);
            }

            $pecatPath = $request->file('pecat')?->store('users/stamps', 'public');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'potpis_path' => $potpisPath,
            'pecat_path' => $pecatPath,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
