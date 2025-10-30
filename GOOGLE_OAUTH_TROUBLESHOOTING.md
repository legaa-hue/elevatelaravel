# Google OAuth Troubleshooting Guide

## Recent Changes Made

### 1. Improved Error Logging
The `GoogleAuthController` now has detailed error logging with specific exception handling:
- **InvalidStateException**: Session expired (user clicked back button)
- **ClientException**: API or credential configuration issues  
- **General Exception**: All other errors with full stack trace

### 2. Fixed Role Parameter Passing
- Register page now passes role as URL parameter: `route('auth.google', { role: form.role })`
- Controller accepts role via both route parameter AND request parameter
- Login page redirects directly without role (will detect existing user's role)

### 3. Enhanced Callback Logic
For **existing users**:
- Updates profile picture from Google
- Updates google_id if not set
- Marks email as verified
- Redirects to appropriate dashboard based on role

For **new users**:
- Checks for pre-selected role from register page
- Creates user with Google data
- If no role: shows SelectRole page

## Testing Steps

### From Register Page (with role selection):
1. Go to `/register`
2. Select a role (Teacher or Student)
3. Click "Sign up with Google"
4. Should redirect to Google OAuth
5. After authentication, should create account and redirect to dashboard

### From Login Page (for existing users):
1. Go to `/login`
2. Click "Sign in with Google"
3. Should detect your existing role
4. Should redirect to your role's dashboard

## Checking Logs

When you test Google sign-in, any errors will be logged to:
```
storage/logs/laravel.log
```

To view recent errors:
```powershell
Get-Content .\storage\logs\laravel.log -Tail 50
```

Look for entries like:
- `Google OAuth State Exception: ...`
- `Google OAuth Client Exception: ...`
- `Google OAuth Error: ...`

## Common Issues & Solutions

### Issue: "Authentication session expired"
**Cause**: InvalidStateException - session state doesn't match  
**Solution**: Clear browser cookies and try again, or try in incognito mode

### Issue: "Google authentication configuration error"
**Cause**: ClientException - Google API rejected the request  
**Solutions**:
1. Verify redirect URI in Google Cloud Console matches: `http://127.0.0.1:8000/auth/google/callback`
2. Check that Google+ API or Google Identity Services API is enabled
3. Verify OAuth consent screen is configured
4. Confirm client secret is correct

### Issue: "Failed to authenticate with Google"
**Cause**: Generic error  
**Solution**: Check the Laravel logs for the specific error message

## Verifying Google Cloud Console Configuration

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Navigate to **APIs & Services > Credentials**
4. Click on your OAuth 2.0 Client ID
5. Under "Authorized redirect URIs", ensure you have:
   ```
   http://127.0.0.1:8000/auth/google/callback
   ```
6. Navigate to **APIs & Services > OAuth consent screen**
7. Ensure the consent screen is configured (at least in testing mode)
8. Navigate to **APIs & Services > Enabled APIs**
9. Verify "Google+ API" or "Google Identity Services API" is enabled

## Testing the Error Messages

Now when you try to sign in with Google and encounter an error, you should see:
1. More specific error messages in the browser
2. Detailed error logs in `storage/logs/laravel.log` with:
   - Exception type
   - Error message
   - Stack trace

This will help identify exactly what's failing.

## Next Steps After Testing

1. Try Google sign-in from the login page
2. Check the error message in the browser
3. Check the Laravel logs for detailed error information
4. Share the specific error message so we can fix the exact issue

## Configuration Verification

Current configuration (verified working):
- ✅ Google Client ID: 291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com
- ✅ Redirect URI: http://127.0.0.1:8000/auth/google/callback
- ✅ reCAPTCHA Site Key: 6LcqJPwrAAAAAN4F0MMjfBsnPUYTIWmni1IN-3go
- ✅ Laravel Socialite installed: v5.23.1
- ✅ Session driver: database
