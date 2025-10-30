# Authentication System Setup Guide

This guide covers the complete authentication system implementation with Google OAuth, email verification, and reCAPTCHA.

## Features Implemented

1. **Role-Based Registration & Login**
   - Users must select a role (Teacher or Student) during registration
   - Role is saved in the database and used for dashboard redirection

2. **Google OAuth Authentication**
   - Users can sign up/sign in with Google
   - New Google users must select their role
   - Existing Google accounts are automatically verified
   - Google users are auto-verified upon registration

3. **Email Verification**
   - Manual registration auto-verifies emails
   - Manual email verification button on login page
   - Verification required before logging in
   - Email notifications via SMTP (Gmail)

4. **reCAPTCHA v2 Integration**
   - Bot protection on login form
   - Graceful fallback in local development

5. **Role-Based Dashboard Redirection**
   - Admin → Admin Dashboard
   - Teacher → Teacher Dashboard
   - Student → Student Dashboard

## Configuration Steps

### 1. Environment Variables (.env)

Update your `.env` file with the following configurations:

```env
# Mail Configuration (Gmail SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD="wbps ifwm ytoe xoqw"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"

# Google OAuth
GOOGLE_CLIENT_ID=291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret-here
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# reCAPTCHA v2
RECAPTCHA_SITE_KEY=your-recaptcha-site-key-here
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key-here
```

### 2. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Configure OAuth consent screen
6. Add authorized redirect URIs:
   - `http://127.0.0.1:8000/auth/google/callback`
   - `http://localhost:8000/auth/google/callback`
   - Add production URL when deployed
7. Copy the Client ID (already in .env) and Client Secret
8. Paste Client Secret in `.env` file

### 3. reCAPTCHA Setup

1. Go to [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
2. Register a new site
3. Select reCAPTCHA v2 → "I'm not a robot" Checkbox
4. Add domains:
   - `localhost`
   - `127.0.0.1`
   - Add production domain when deployed
5. Copy Site Key and Secret Key
6. Paste them in `.env` file
7. Update `Login.vue` line with reCAPTCHA site key:
   ```vue
   <div class="g-recaptcha" data-sitekey="your-actual-site-key-here"></div>
   ```

### 4. Database Migration

Ensure your users table has all required columns:

```bash
php artisan migrate
```

Required columns in `users` table:
- `first_name`
- `last_name`
- `email`
- `email_verified_at`
- `password`
- `role` (enum: 'student', 'teacher', 'admin')
- `google_id` (nullable)
- `profile_picture` (nullable)

### 5. Gmail App Password Setup

Since you're using Gmail SMTP, you need an App Password:

1. Go to Google Account → Security
2. Enable 2-Factor Authentication (if not enabled)
3. Go to "App passwords"
4. Generate a new app password for "Mail"
5. Use the generated password (already provided: `wbps ifwm ytoe xoqw`)
6. Update `MAIL_USERNAME` with your actual Gmail address

### 6. Build Frontend Assets

```bash
npm install
npm run build
# or for development
npm run dev
```

## Testing the Features

### Test Email Verification

1. Register with a manual email
2. Check your inbox for verification email
3. Click the verification link
4. Try logging in

### Test Google OAuth

1. Click "Sign up with Google" or "Continue with Google"
2. Select a Google account
3. If new user, select role (Teacher/Student)
4. Check that you're redirected to appropriate dashboard

### Test Role-Based Login

1. Login as Teacher → Should go to Teacher Dashboard
2. Login as Student → Should go to Student Dashboard
3. Login as Admin → Should go to Admin Dashboard

### Test reCAPTCHA

1. Go to login page
2. Complete the reCAPTCHA checkbox
3. Try logging in without completing reCAPTCHA (should fail in production)

### Test Manual Email Verification Button

1. Go to login page
2. Type an email address in the email field
3. Click "Verify Email" button
4. Check inbox for verification email

## Important Routes

- Login: `/login`
- Register: `/register`
- Google OAuth: `/auth/google`
- Google Callback: `/auth/google/callback`
- Role Selection: `/auth/google/complete`
- Email Verification: `/verify-email/{id}/{hash}`
- Manual Verification: `POST /email/send-verification`

## Security Features

1. **Email Verification Required**: Users cannot login without verified email
2. **reCAPTCHA Protection**: Prevents bot attacks on login
3. **Rate Limiting**: Built-in Laravel rate limiting on login attempts
4. **Role Validation**: Strict validation on role selection
5. **CSRF Protection**: All forms are CSRF protected
6. **Password Hashing**: Bcrypt hashing for all passwords

## Troubleshooting

### Email Not Sending

1. Check SMTP credentials in `.env`
2. Ensure Gmail App Password is correct
3. Check if 2FA is enabled on Gmail account
4. Test with: `php artisan tinker` then `Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });`

### Google OAuth Not Working

1. Verify Client ID and Secret in `.env`
2. Check redirect URI matches exactly in Google Console
3. Ensure Google+ API is enabled
4. Clear browser cookies and try again

### reCAPTCHA Not Working

1. Check site key is correctly set in `Login.vue`
2. Verify domain is added in reCAPTCHA admin
3. Check secret key in `.env`
4. In development, reCAPTCHA validation is lenient

### Role Selection Not Appearing

1. Clear browser cache
2. Check session is working: `php artisan session:table` then `php artisan migrate`
3. Verify SESSION_DRIVER is set correctly in `.env`

## Files Modified/Created

### Controllers
- `app/Http/Controllers/Auth/GoogleAuthController.php` (NEW)
- `app/Http/Controllers/Auth/ManualEmailVerificationController.php` (NEW)
- `app/Http/Controllers/Auth/RegisteredUserController.php` (MODIFIED)
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (MODIFIED)

### Models
- `app/Models/User.php` (MODIFIED - implements MustVerifyEmail)

### Requests
- `app/Http/Requests/Auth/LoginRequest.php` (MODIFIED - added reCAPTCHA validation)

### Rules
- `app/Rules/RecaptchaRule.php` (NEW)

### Views
- `resources/js/Pages/Auth/Login.vue` (MODIFIED)
- `resources/js/Pages/Auth/Register.vue` (MODIFIED)
- `resources/js/Pages/Auth/SelectRole.vue` (NEW)

### Routes
- `routes/auth.php` (MODIFIED)

### Config
- `config/services.php` (MODIFIED - added Google and reCAPTCHA)
- `.env` (MODIFIED)

## Next Steps

1. Set up proper email templates for verification emails
2. Configure production domain in Google Console
3. Add reCAPTCHA to registration form (optional)
4. Implement email verification reminder system
5. Add password reset functionality with email
6. Set up proper error logging for production

## Support

If you encounter any issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Browser console for JavaScript errors
3. Network tab for failed requests
4. Database for proper column existence

---

**Remember to update placeholders:**
- Replace `your-email@gmail.com` with your actual Gmail
- Replace `your-google-client-secret-here` with actual Google Client Secret
- Replace `your-recaptcha-site-key-here` with actual reCAPTCHA Site Key
- Replace `your-recaptcha-secret-key-here` with actual reCAPTCHA Secret Key
- Update the hardcoded site key in `Login.vue` component
