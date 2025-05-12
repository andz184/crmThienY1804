<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rules\File;

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
    public function update(ProfileUpdateRequest $profileRequest, Request $request): RedirectResponse
    {
        $user = $request->user();

        // Validate additional fields (certificate, id_card)
        $request->validate([
            'certificate' => [
                'nullable',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max(5 * 1024), // 5MB max size
            ],
            'id_card' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png']) // Only images for ID card
                    ->max(5 * 1024), // 5MB max size
            ],
        ]);

        // Update Name/Email (from ProfileUpdateRequest)
        $user->fill($profileRequest->validated());

        // Handle email verification status change
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle Certificate Upload
        if ($request->hasFile('certificate') && $request->file('certificate')->isValid()) {
            if ($user->certificate_path) {
                Storage::disk('public')->delete($user->certificate_path);
            }
            $path = $request->file('certificate')->store('certificates', 'public');
            $user->certificate_path = $path;
        }

        // Handle ID Card Upload
        if ($request->hasFile('id_card') && $request->file('id_card')->isValid()) {
            if ($user->id_card_path) {
                Storage::disk('public')->delete($user->id_card_path);
            }
            // Store in a different directory, e.g., 'id_cards'
            $path = $request->file('id_card')->store('id_cards', 'public');
            $user->id_card_path = $path;
        }

        // Save all changes
        $user->save();

        // Redirect back
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

    /**
     * Display the user's profile form.
     */
    public function show(Request $request): View
    {
        $user = Auth::user();

        // Optionally eager load roles if you display them
        // $user->load('roles');

        return view('profile.show', [
            'user' => $user,
        ]);
    }
}
