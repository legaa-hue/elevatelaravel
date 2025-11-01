<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualEmailVerificationController extends Controller
{
    /**
     * Send a verification email to the specified email address
     */
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->back()->with('error', 'This email is already verified.');
        }

        // Send verification email
        $user->sendEmailVerificationNotification();

        return redirect()->back()->with('success', 'Verification email sent successfully.');
    }
}
