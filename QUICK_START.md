# ElevateGS Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Prerequisites
- PHP 8.2+
- Composer installed
- Node.js 18+ & npm installed
- MySQL/PostgreSQL database
- Mail server configured (or use Mailtrap for testing)

---

## Step 1: Configure Environment

### 1.1 Copy `.env.example` to `.env`
```bash
cp .env.example .env
```

### 1.2 Update `.env` with your settings
```env
APP_NAME=ElevateGS
APP_ENV=local
APP_KEY=base64:...  # Already set by artisan key:generate
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elevategs
DB_USERNAME=root
DB_PASSWORD=your_password

# Mail (use Mailtrap for testing)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@elevategs.test
MAIL_FROM_NAME="ElevateGS"

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# Queue
QUEUE_CONNECTION=database

# JWT (already set during installation)
JWT_SECRET=<already-generated>
JWT_TTL=120
JWT_REFRESH_TTL=20160
```

---

## Step 2: Run Database Migrations

```bash
# Create database (if not exists)
mysql -u root -p
CREATE DATABASE elevategs;
EXIT;

# Run migrations
php artisan migrate

# Optional: Seed with sample data
php artisan db:seed
```

---

## Step 3: Start the Application

### 3.1 Start Laravel Server
```bash
php artisan serve
```
Server will run at: http://localhost:8000

### 3.2 Start Queue Worker (for emails)
Open a new terminal and run:
```bash
php artisan queue:work
```

### 3.3 Start Vite Dev Server (for frontend hot reload)
Open another terminal and run:
```bash
npm run dev
```

---

## Step 4: Test the Application

### 4.1 Open Browser
Navigate to: http://localhost:8000

### 4.2 Create an Account
1. Click "Get Started" or "Register"
2. Select your role (Teacher or Student)
3. Fill in registration form
4. Click "Create Account"

### 4.3 Activate Account
1. Check your email (or Mailtrap inbox)
2. Click "Activate Account" button
3. Wait for auto-redirect or click "Go to Login Now"

### 4.4 Login
1. Enter your email and password
2. Check "Remember me" for longer session
3. Click "Sign In"
4. You'll be redirected to your dashboard!

---

## 🧪 Test JWT API

### Test API Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your@email.com",
    "password": "your_password",
    "remember": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 604800,
  "user": {
    "id": 1,
    "name": "Your Name",
    "email": "your@email.com",
    "role": "student"
  }
}
```

### Test Protected Endpoint
```bash
# Replace <token> with the token from login response
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer <token>"
```

---

## 🔌 Test Offline Mode

### In Browser DevTools:
1. Open your app in Chrome/Edge
2. Press F12 to open DevTools
3. Go to "Network" tab
4. Change "No throttling" to "Offline"
5. Navigate around the app
6. **Expected:** Yellow offline banner appears, cached data loads
7. Try to submit something
8. **Expected:** Action queued ("1 pending action")
9. Change back to "Online"
10. **Expected:** Green banner appears, actions sync automatically

---

## 📱 Install as PWA

### Desktop (Chrome/Edge):
1. Open app in browser
2. Look for install icon in address bar (⊕)
3. Click "Install"
4. App opens in standalone window
5. Icon added to desktop/start menu

### Mobile (Android/iOS):
1. Open app in browser
2. Tap browser menu (⋮)
3. Select "Add to Home Screen" or "Install App"
4. App added to home screen
5. Opens like a native app

---

## 🎯 Common Tasks

### Create a New Teacher Account
```
1. Register with role: Teacher
2. Activate via email
3. Login → Redirected to Teacher Dashboard
```

### Create a New Student Account
```
1. Register with role: Student
2. Activate via email
3. Login → Redirected to Student Dashboard
```

### Test Email Activation
```
1. Register account
2. Check Mailtrap inbox (or your email)
3. Click activation link
4. Should see success page
```

### Test Remember Me
```
1. Login with "Remember me" checked
2. Close browser completely
3. Reopen browser and go to app
4. Should still be logged in (token in localStorage)
```

### Test Without Remember Me
```
1. Login without "Remember me"
2. Close browser tab (not whole browser)
3. Reopen tab and go to app
4. Should be logged out (token was in sessionStorage)
```

---

## 🐛 Troubleshooting

### "419 Page Expired" on Login
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
```
Then clear browser cookies and try again.

### Emails Not Sending
**Check:**
1. Queue worker is running: `php artisan queue:work`
2. Mail settings in `.env` are correct
3. Check `storage/logs/laravel.log` for errors

### JWT Token Not Working
**Check:**
```bash
# Make sure JWT secret is set
php artisan jwt:secret

# Check if api routes are working
php artisan route:list --path=api
```

### Frontend Not Building
**Solution:**
```bash
# Clear node modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Offline Mode Not Working
**Check:**
1. Open DevTools → Console for errors
2. Application → Service Workers → Check if registered
3. Application → IndexedDB → Check if ElevateGS_Offline exists
4. Try hard refresh: Ctrl+Shift+R

---

## 📚 Next Steps

### For Teachers:
1. Create courses
2. Add assignments
3. Invite students
4. Grade submissions
5. Export reports

### For Students:
1. Join courses
2. View assignments
3. Submit work
4. Check grades
5. Track progress

### For Admins:
1. Manage users
2. View audit logs
3. Manage academic years
4. Configure system settings

---

## 🔗 Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh --seed

# Generate new app key
php artisan key:generate

# Generate new JWT secret
php artisan jwt:secret

# List all routes
php artisan route:list

# Build for production
npm run build

# Run in development
npm run dev

# Start queue worker
php artisan queue:work

# Start server
php artisan serve
```

---

## 📖 Documentation

- **Complete System:** `AUTH_SYSTEM_IMPLEMENTATION.md`
- **JWT & Offline:** `JWT_OFFLINE_IMPLEMENTATION.md`
- **Testing Guide:** `TESTING_GUIDE.md`
- **This Guide:** `QUICK_START.md`

---

## 🎉 You're All Set!

Your ElevateGS application is now running with:
- ✅ Email activation system
- ✅ JWT authentication
- ✅ Offline PWA support
- ✅ Automatic sync
- ✅ Remember Me feature
- ✅ Role-based access

**Happy coding!** 🚀

---

**Need Help?**  
Check the documentation files or review browser console/Laravel logs for errors.
