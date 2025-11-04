<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('ProfileSettings', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'last_name' => ['required', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'], // 2MB max
            'remove_profile_picture' => ['boolean']
        ]);

        // Handle profile picture removal
        if ($request->boolean('remove_profile_picture')) {
            if ($user->profile_picture && !str_starts_with($user->profile_picture, 'http')) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = null;
        }
        
        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if it exists and is not a Google URL
            if ($user->profile_picture && !str_starts_with($user->profile_picture, 'http')) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            
            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $user->profile_picture = $path;
        }

        // Update other fields
        $user->first_name = $validated['first_name'];
        $user->middle_initial = $validated['middle_initial'] ?? null;
        $user->last_name = $validated['last_name'];
        
        // Update the name field (full name)
        $user->name = trim($validated['first_name'] . ' ' . 
                          ($validated['middle_initial'] ? $validated['middle_initial'] . '. ' : '') . 
                          $validated['last_name']);

        // Debug: Log the values
        \Log::info('Saving user profile', [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'middle_initial' => $user->middle_initial,
            'last_name' => $user->last_name,
            'name' => $user->name
        ]);

        $user->save();

        return back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Prevent Google sign-in users from changing password
        if ($user->google_id) {
            return back()->withErrors([
                'password' => 'Cannot change password for Google sign-in accounts.'
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Password updated successfully');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
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
