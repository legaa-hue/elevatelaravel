<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;
use Inertia\Response;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google for authentication
     */
    public function redirect(Request $request, ?string $role = null): RedirectResponse
    {
        // If role is provided via route parameter, store it in session
        if ($role && in_array($role, ['teacher', 'student'])) {
            session(['pending_google_role' => $role]);
        }
        
        // If role is provided via request parameter, store it in session
        if ($request->has('role') && in_array($request->role, ['teacher', 'student'])) {
            session(['pending_google_role' => $request->role]);
        }
        
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google callback
     */
    public function callback(Request $request): RedirectResponse|Response
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists
            $user = User::where('email', $googleUser->email)->first();
            
            // Check if this is coming from register page (has pending_google_role)
            $preSelectedRole = $request->session()->get('pending_google_role');
            $isFromRegisterPage = !empty($preSelectedRole);
            
            if ($user) {
                // User already exists
                if ($isFromRegisterPage) {
                    // They're trying to register but account already exists
                    $request->session()->forget('pending_google_role');
                    return redirect()->route('register')->with('error', 'This email is already registered. Please use the login page instead.');
                }
                
                // Coming from login page - allow login
                // Update Google profile picture if not set
                if (!$user->profile_picture && $googleUser->avatar) {
                    $user->profile_picture = $googleUser->avatar;
                }
                
                // Update Google ID if not set
                if (!$user->google_id) {
                    $user->google_id = $googleUser->id;
                }
                
                $user->save();
                
                // Log the user in
                Auth::login($user);
                
                // Check if email is verified
                if (!$user->hasVerifiedEmail()) {
                    return redirect()->route('verification.notice');
                }
                
                // Redirect based on role
                return $this->redirectBasedOnRole($user);
            } else {
                // New user - check if role was pre-selected (from register page)
                
                if ($preSelectedRole && in_array($preSelectedRole, ['teacher', 'student'])) {
                    // Role already selected, create user directly
                    $user = $this->createUserFromGoogle($googleUser, $preSelectedRole);
                    
                    // Clear the session
                    $request->session()->forget('pending_google_role');
                    
                    // Log the user in
                    Auth::login($user);
                    
                    // Redirect to email verification page
                    return redirect()->route('verification.notice');
                } else {
                    // No role selected - store Google user data and show role selection
                    session([
                        'google_user' => [
                            'id' => $googleUser->id,
                            'email' => $googleUser->email,
                            'name' => $googleUser->name,
                            'avatar' => $googleUser->avatar,
                        ]
                    ]);
                    
                    // Redirect to role selection page
                    return Inertia::render('Auth/SelectRole');
                }
            }
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // State mismatch - usually happens when user clicks back button
            \Log::error('Google OAuth State Exception: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Authentication session expired. Please try again.');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // API or credential issues
            \Log::error('Google OAuth Client Exception: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Google authentication configuration error. Please contact support.');
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('login')->with('error', 'Failed to authenticate with Google. Please try again. Error: ' . $e->getMessage());
        }
    }

    /**
     * Complete registration with role selection
     */
    public function completeRegistration(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => 'required|in:teacher,student',
        ]);

        $googleUser = session('google_user');
        
        if (!$googleUser) {
            return redirect()->route('login')->with('error', 'Session expired. Please try again.');
        }

        // Create new user using helper method
        $user = $this->createUserFromGoogle((object)$googleUser, $request->role);

        // Clear session data
        session()->forget('google_user');

        // Log the user in
        Auth::login($user);

        // Redirect to email verification page
        return redirect()->route('verification.notice');
    }

    /**
     * Create a new user from Google OAuth data
     */
    private function createUserFromGoogle($googleUser, string $role): User
    {
        // Parse name into first and last name
        $nameParts = explode(' ', $googleUser->name ?? '', 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName = $nameParts[1] ?? '';

        $user = User::create([
            'name' => $googleUser->name ?? ($firstName . ' ' . $lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $googleUser->email,
            'google_id' => $googleUser->id,
            'profile_picture' => $googleUser->avatar ?? null,
            'password' => Hash::make(uniqid()), // Random password for Google users
            'role' => $role,
            'email_verified_at' => null, // Require email verification even for Google users
        ]);

        // Fire registered event to trigger verification email
        event(new Registered($user));

        return $user;
    }

    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return redirect()->route('dashboard');
        } elseif ($user->role === 'student') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard');
    }
}
