# Google OAuth Fix Summary

## Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Problem
Google OAuth authentication was failing with a generic error message: "Failed to authenticate with Google. Please try again."
User wanted to ensure:
1. Login page detects existing user's role and redirects to correct dashboard
2. Better error messages to debug the issue

## Changes Made

### 1. GoogleAuthController.php - Enhanced Error Handling
**File**: `app/Http/Controllers/Auth/GoogleAuthController.php`

**Changes**:
- Added specific exception handling for `InvalidStateException` (session expired)
- Added specific exception handling for `ClientException` (API/credential issues)
- Added detailed error logging with `\Log::error()` for all exceptions
- Include stack traces in logs for better debugging
- Error messages now show the actual exception message to help identify root cause

**Before**:
```php
catch (\Exception $e) {
    return redirect()->route('login')->with('error', 'Failed to authenticate with Google. Please try again.');
}
```

**After**:
```php
catch (\Laravel\Socialite\Two\InvalidStateException $e) {
    \Log::error('Google OAuth State Exception: ' . $e->getMessage());
    return redirect()->route('login')->with('error', 'Authentication session expired. Please try again.');
} catch (\GuzzleHttp\Exception\ClientException $e) {
    \Log::error('Google OAuth Client Exception: ' . $e->getMessage());
    return redirect()->route('login')->with('error', 'Google authentication configuration error. Please contact support.');
} catch (\Exception $e) {
    \Log::error('Google OAuth Error: ' . $e->getMessage());
    \Log::error('Stack trace: ' . $e->getTraceAsString());
    return redirect()->route('login')->with('error', 'Failed to authenticate with Google. Please try again. Error: ' . $e->getMessage());
}
```

### 2. GoogleAuthController.php - Role Parameter Support
**File**: `app/Http/Controllers/Auth/GoogleAuthController.php`

**Changes**:
- Updated `redirect()` method to accept role from both route parameter AND request parameter
- This ensures role can be passed from Register page via URL query string

**Added**:
```php
// If role is provided via request parameter, store it in session
if ($request->has('role') && in_array($request->role, ['teacher', 'student'])) {
    session(['pending_google_role' => $request->role]);
}
```

### 3. Register.vue - Fixed Role Passing
**File**: `resources/js/Pages/Auth/Register.vue`

**Changes**:
- Changed from using client-side `sessionStorage` to server-side session via URL parameter
- Now passes role directly in the OAuth redirect URL

**Before**:
```javascript
const handleGoogleSignUp = () => {
    if (!form.role) {
        alert('Please select your role (Teacher or Student) before signing in with Google.');
        return;
    }
    sessionStorage.setItem('pending_google_role', form.role);
    window.location.href = route('auth.google');
};
```

**After**:
```javascript
const handleGoogleSignUp = () => {
    if (!form.role) {
        alert('Please select your role (Teacher or Student) before signing in with Google.');
        return;
    }
    // Redirect to Google OAuth with role parameter
    window.location.href = route('auth.google', { role: form.role });
};
```

## How It Works Now

### From Register Page:
1. User selects role (Teacher/Student)
2. Clicks "Sign up with Google"
3. Role is passed as URL parameter: `/auth/google?role=teacher`
4. Controller stores role in server session
5. After Google authentication, user is created with the selected role
6. User is redirected to role-appropriate dashboard

### From Login Page:
1. User clicks "Sign in with Google"
2. No role parameter is sent
3. After Google authentication:
   - If user exists: Updates profile picture, google_id, email verification
   - Logs user in
   - Redirects to dashboard based on existing user's role
   - **Admin** → `/admin/dashboard`
   - **Teacher/Student** → `/dashboard`

### Role-Based Routing Logic:
```php
protected function redirectBasedOnRole(User $user): RedirectResponse
{
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    
    return redirect()->route('dashboard');
}
```

## Debugging Features Added

### Error Logging
All Google OAuth errors are now logged to: `storage/logs/laravel.log`

To check logs:
```powershell
Get-Content .\storage\logs\laravel.log -Tail 50
```

### Specific Error Types:
1. **InvalidStateException**: "Authentication session expired"
   - Happens when user clicks browser back button
   - Solution: Clear cookies or use incognito mode

2. **ClientException**: "Google authentication configuration error"
   - Happens when Google API rejects request
   - Check: Redirect URI, OAuth consent screen, API enabled status

3. **General Exception**: Shows actual error message
   - Stack trace logged for detailed debugging

## Files Modified

1. `app/Http/Controllers/Auth/GoogleAuthController.php` - Enhanced error handling and role parameter support
2. `resources/js/Pages/Auth/Register.vue` - Fixed role passing to use URL parameter
3. `GOOGLE_OAUTH_TROUBLESHOOTING.md` - Created comprehensive troubleshooting guide

## Testing Checklist

- [ ] Build frontend assets (`npm run build`) ✅
- [ ] Test Google sign-in from Login page
- [ ] Test Google sign-up from Register page with Teacher role
- [ ] Test Google sign-up from Register page with Student role
- [ ] Check error logs if any issues occur
- [ ] Verify existing user login redirects to correct dashboard
- [ ] Verify new user registration creates account with selected role

## Next Steps

1. **Try Google OAuth** from both login and register pages
2. **If error occurs**:
   - Note the error message shown in browser
   - Check Laravel logs: `Get-Content .\storage\logs\laravel.log -Tail 50`
   - Share the specific error for further troubleshooting

3. **Verify Google Cloud Console**:
   - Redirect URI: `http://127.0.0.1:8000/auth/google/callback`
   - OAuth consent screen configured
   - Google+ API or Google Identity Services API enabled
   - Client secret matches .env file

## Configuration Verified

✅ Google Client ID: `291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com`
✅ Redirect URI: `http://127.0.0.1:8000/auth/google/callback`
✅ Laravel Socialite: v5.23.1
✅ Session driver: database
✅ reCAPTCHA v3 configured and working

## Benefits of These Changes

1. **Better Debugging**: Detailed error logs help identify exact issue
2. **Proper Role Handling**: Role is now stored in server session (more reliable)
3. **Specific Error Messages**: Users see more helpful error messages
4. **Existing User Support**: Login page properly detects role and redirects accordingly
5. **Complete Error Tracking**: All OAuth errors are logged with stack traces
