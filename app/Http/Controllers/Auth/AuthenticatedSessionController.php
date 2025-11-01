<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect based on user role
        $user = $request->user();
        
        // Check if account is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Please activate your account via the email we sent. Didn\'t receive it? Contact support.');
        }
        
        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Please activate your account by verifying your email address. Didn\'t receive it? Resend activation email below.');
        }
        
        if ($user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        } elseif ($user->role === 'teacher') {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->role === 'student') {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
