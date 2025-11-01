<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountActivation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountActivationController extends Controller
{
    /**
     * Activate user account using token
     */
    public function activate(Request $request, string $token)
    {
        $user = User::whereNotNull('activation_token')
            ->where('is_active', false)
            ->first();

        if (!$user) {
            return Inertia::render('Auth/ActivationResult', [
                'success' => false,
                'message' => 'Activation link is invalid or has already been used.',
            ]);
        }

        if (!$user->isActivationTokenValid($token)) {
            return Inertia::render('Auth/ActivationResult', [
                'success' => false,
                'message' => 'Activation link has expired or is invalid.',
                'canResend' => true,
                'email' => $user->email,
            ]);
        }

        // Activate the account
        $user->activate();

        return Inertia::render('Auth/ActivationResult', [
            'success' => true,
            'message' => 'Your account has been successfully activated!',
        ]);
    }

    /**
     * Resend activation email
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', false)
            ->first();

        if (!$user) {
            return back()->with('error', 'Account not found or already activated.');
        }

        // Generate new token
        $token = $user->generateActivationToken();

        // Send activation email
        $user->notify(new AccountActivation($token, $user->first_name ?? $user->name));

        return back()->with('success', 'Activation email has been resent. Please check your inbox.');
    }
}
