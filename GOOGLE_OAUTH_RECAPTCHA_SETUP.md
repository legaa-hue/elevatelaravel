# Google OAuth & reCAPTCHA Setup Instructions

## 🔴 Issue 1: Fix Google OAuth `redirect_uri_mismatch`

### Step 1: Go to Google Cloud Console
1. Visit: https://console.cloud.google.com/apis/credentials
2. Sign in with your Google account (lenardanthonygaa@gmail.com)

### Step 2: Find Your OAuth 2.0 Client
1. Look for your OAuth 2.0 Client ID: `291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com`
2. Click on it to edit

### Step 3: Add Authorized Redirect URIs
In the "Authorized redirect URIs" section, add EXACTLY these URLs:

```
http://127.0.0.1:8000/auth/google/callback
http://localhost:8000/auth/google/callback
```

⚠️ **IMPORTANT:** 
- Must be EXACT match (no trailing slashes)
- Use `http://` not `https://` for local development
- Port must match (8000)
- Path must be `/auth/google/callback`

### Step 4: Save Changes
1. Click "SAVE" at the bottom
2. Wait a few seconds for changes to propagate

### Step 5: Get Your Client Secret
While you're on the same page:
1. Copy the "Client secret" value
2. Update your `.env` file:
   ```
   GOOGLE_CLIENT_SECRET=paste-the-secret-here
   ```

### Step 6: Clear Config Cache
Run in terminal:
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🔑 Issue 2: Get reCAPTCHA v3 Keys

### Step 1: Go to reCAPTCHA Admin Console
Visit: https://www.google.com/recaptcha/admin/create

### Step 2: Register a New Site
Fill in the form:

**Label:**
```
ElevateGS Local Development
```

**reCAPTCHA type:**
- ✅ Select **"reCAPTCHA v3"** (NOT v2!)
  - This is the invisible version that runs in background

**Domains:**
Add these domains (one per line):
```
localhost
127.0.0.1
```

**Accept the Terms:**
- ✅ Check "Accept the reCAPTCHA Terms of Service"

**Submit:**
- Click "Submit" button

### Step 3: Copy Your Keys
You'll see two keys:

**Site Key:**
```
Example: 6LdxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxYYYY
```

**Secret Key:**
```
Example: 6LdxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxZZZZ
```

### Step 4: Update .env File
Open your `.env` file and replace:

```env
# reCAPTCHA v3 (change from v2 to v3)
RECAPTCHA_SITE_KEY=paste-your-site-key-here
RECAPTCHA_SECRET_KEY=paste-your-secret-key-here
```

### Step 5: Clear Config and Rebuild
Run these commands:
```bash
php artisan config:clear
php artisan cache:clear
npm run build
```

---

## ✅ Verification Steps

### Test Google OAuth:
1. Go to: http://127.0.0.1:8000/login
2. Click "Continue with Google"
3. Should redirect to Google sign-in (no error)
4. Sign in with Google
5. Should redirect back and prompt for role selection (if new user)

### Test reCAPTCHA:
1. Go to: http://127.0.0.1:8000/login
2. Look for "Protected by reCAPTCHA" text at bottom
3. Fill in email/password
4. Click "Sign In"
5. reCAPTCHA should work silently in background
6. Should login successfully

---

## 🆘 Quick Troubleshooting

### Still Getting redirect_uri_mismatch?

**Check these common issues:**

1. **Exact URL Match:**
   - Console: `http://127.0.0.1:8000/auth/google/callback`
   - .env: `GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"`
   - APP_URL: `http://127.0.0.1:8000`

2. **No Trailing Slash:**
   - ❌ Wrong: `http://127.0.0.1:8000/auth/google/callback/`
   - ✅ Correct: `http://127.0.0.1:8000/auth/google/callback`

3. **Port Number:**
   - Your server must run on port 8000
   - Check: `php artisan serve` (defaults to 8000)

4. **Wait Time:**
   - After saving in Google Console, wait 30-60 seconds

5. **Clear Cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### reCAPTCHA Not Working?

1. **Wrong Version:**
   - Make sure you selected v3, not v2
   - v2 has a checkbox widget (we don't want that)
   - v3 is invisible (correct!)

2. **Domain Issues:**
   - Add both `localhost` and `127.0.0.1`
   - Don't include `http://` or port numbers

3. **Config Not Updated:**
   ```bash
   php artisan config:clear
   npm run build
   ```

---

## 📋 Quick Reference

### Your Current Configuration:
```
APP_URL: http://127.0.0.1:8000
Google Client ID: 291681305793-7k3unhkoi9srt08lrq75etbg1jafmtsi.apps.googleusercontent.com
Gmail: lenardanthonygaa@gmail.com
Redirect URI: http://127.0.0.1:8000/auth/google/callback
```

### What You Need to Get:
- [ ] Google Client Secret
- [ ] reCAPTCHA v3 Site Key
- [ ] reCAPTCHA v3 Secret Key

### Commands to Run After Setup:
```bash
php artisan config:clear
php artisan cache:clear
npm run build
php artisan serve
```

---

## 🎯 Expected Behavior After Setup

### Google OAuth Flow:
1. Click "Continue with Google" → Redirects to Google
2. Sign in with Google account
3. First time: Select role (Teacher/Student)
4. Redirects to dashboard
5. No errors!

### Login Flow:
1. Enter email and password
2. reCAPTCHA runs invisibly
3. See "Protected by reCAPTCHA" text
4. Login successful
5. Redirect to dashboard

---

**Need Help?**
- Google Cloud Console: https://console.cloud.google.com/apis/credentials
- reCAPTCHA Admin: https://www.google.com/recaptcha/admin
- Check Laravel logs: `storage/logs/laravel.log`
