<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountActivation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:'.User::class
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:teacher,student',
        ], [
            'email.unique' => 'This email is already registered. Please login or use password reset if you forgot your password.',
            'email.email' => 'Please enter a valid email address that can receive messages.',
        ]);

        // Check for disposable/temporary email services
        $disposableDomains = [
            'tempmail.com', 'guerrillamail.com', '10minutemail.com', 'mailinator.com',
            'throwaway.email', 'temp-mail.org', 'fakeinbox.com', 'trashmail.com',
            'yopmail.com', 'getnada.com', 'maildrop.cc', 'sharklasers.com'
        ];

        $emailDomain = substr(strrchr($request->email, "@"), 1);
        
        if (in_array(strtolower($emailDomain), $disposableDomains)) {
            throw ValidationException::withMessages([
                'email' => 'Please use a permanent email address. Temporary email services are not allowed.',
            ]);
        }

        // Check if email was verified during registration
        $emailVerification = \DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->where('verified', true)
            ->first();

        if (!$emailVerification) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email address before creating an account.',
            ]);
        }

        // Create user with verified email and active account
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true, // Account is active immediately
            'email_verified_at' => now(), // Email is already verified
        ]);

        // Delete the verification record
        \DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->delete();

        event(new Registered($user));

        // Don't auto-login, redirect to login page
        return redirect()->route('login')->with('status', '✅ Account created successfully! You can now sign in with your credentials.');
    }
}
