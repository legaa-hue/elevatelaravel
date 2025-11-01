# ElevateGS Testing Guide

## Test Scenarios

### 1. Registration Flow Tests

#### Test 1.1: Role Selection Enforcement
**Steps:**
1. Navigate to `/register`
2. Try clicking "Sign up with Google" without selecting role
3. **Expected:** Alert shows "⚠️ Please select your role before signing up"
4. Try clicking "Create Account" without selecting role
5. **Expected:** Button is disabled, warning message shows

#### Test 1.2: Successful Registration
**Steps:**
1. Navigate to `/register`
2. Select "Student" role
3. Fill in all fields:
   - First Name: Test
   - Last Name: Student
   - Email: test@example.com
   - Password: Password123!
   - Confirm Password: Password123!
4. Click "Create Account"
5. **Expected:** 
   - Redirected to `/login`
   - Success message: "Account created successfully! Please check your email..."
   - Email sent to test@example.com

#### Test 1.3: Google Registration with Role
**Steps:**
1. Navigate to `/register`
2. Click "Sign up with Google" without selecting role
3. **Expected:** Alert shows
4. Select "Teacher" role
5. Click "Sign up with Google"
6. **Expected:** Redirected to Google OAuth with role parameter

---

### 2. Email Activation Tests

#### Test 2.1: Valid Activation Link
**Steps:**
1. Register new account
2. Check email inbox
3. Click "Activate Account" button
4. **Expected:**
   - Redirected to activation success page
   - Green checkmark shown
   - Countdown starts: "Redirecting to Login in 3... 2... 1..."
   - Auto-redirect to `/login` after 3 seconds
   - Account `is_active` = true in database

#### Test 2.2: Expired Activation Link
**Steps:**
1. Get activation link from email
2. Wait 24+ hours (or manually expire token in DB)
3. Click activation link
4. **Expected:**
   - Error page shown with red X icon
   - Message: "Activation link has expired or is invalid"
   - "Resend Activation Email" button visible

#### Test 2.3: Resend Activation Email
**Steps:**
1. On expired activation error page
2. Click "Resend Activation Email"
3. **Expected:**
   - Success message shown
   - New email sent with new 24-hour token
   - New link works successfully

---

### 3. Login Flow Tests

#### Test 3.1: Login with Inactive Account
**Steps:**
1. Register account but don't activate
2. Navigate to `/login`
3. Enter correct credentials
4. Click "Sign In"
5. **Expected:**
   - Login fails
   - Error message: "Please activate your account via the email we sent"
   - Resend activation option available

#### Test 3.2: Login with Active Account
**Steps:**
1. Activate account via email
2. Navigate to `/login`
3. Enter correct credentials
4. Check "Remember me"
5. Click "Sign In"
6. **Expected:**
   - Successfully logged in
   - Redirected to appropriate dashboard (Teacher/Student)
   - JWT token stored in localStorage (remember me)

#### Test 3.3: Login without Remember Me
**Steps:**
1. Navigate to `/login`
2. Enter credentials
3. Leave "Remember me" unchecked
4. Sign in
5. **Expected:**
   - JWT token stored in sessionStorage only
   - Token expires when browser closes

---

### 4. JWT Authentication Tests

#### Test 4.1: API Login with JWT
**API Request:**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "Password123!",
  "remember": true
}
```

**Expected Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 604800,
  "remember": true,
  "user": {
    "id": 1,
    "name": "Test Student",
    "email": "test@example.com",
    "role": "student"
  }
}
```

#### Test 4.2: Access Protected Route
**API Request:**
```bash
GET /api/auth/me
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Expected Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Test Student",
    "email": "test@example.com",
    "role": "student",
    "is_active": true
  }
}
```

#### Test 4.3: Token Refresh
**API Request:**
```bash
POST /api/auth/refresh
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Expected Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 7200
}
```

#### Test 4.4: Logout
**API Request:**
```bash
POST /api/auth/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

---

### 5. Offline/PWA Tests

#### Test 5.1: Offline Mode Detection
**Steps:**
1. Login to application
2. Navigate to dashboard
3. Open browser DevTools → Network tab
4. Set network to "Offline"
5. **Expected:**
   - Yellow offline banner appears at top
   - Message: "You're offline - Your changes will be saved and synced..."

#### Test 5.2: Offline Data Access
**Steps:**
1. While online, navigate through:
   - Courses page
   - Assignments page
   - Grades page
2. Go offline (DevTools → Network → Offline)
3. Navigate through same pages
4. **Expected:**
   - All previously viewed data still accessible
   - Read-only mode (no submissions allowed)
   - Cached data displayed from IndexedDB

#### Test 5.3: Offline Action Queuing
**Steps:**
1. While online, view a course
2. Go offline
3. Try to submit an assignment
4. **Expected:**
   - Submission queued in IndexedDB
   - "Pending actions" counter shows in offline banner
   - Action stored in `pendingActions` store

#### Test 5.4: Online Sync
**Steps:**
1. With pending actions queued
2. Go back online
3. **Expected:**
   - Green banner briefly shows: "Back online! Syncing..."
   - Pending actions automatically sync to server
   - Counter decreases as actions complete
   - Data updated in real-time

#### Test 5.5: JWT Validation Offline
**Steps:**
1. Login while online
2. Go offline
3. Refresh page
4. **Expected:**
   - Still logged in (JWT validated from storage)
   - User data loaded from IndexedDB
   - Offline banner visible

---

### 6. PWA Installation Tests

#### Test 6.1: Install Prompt
**Steps:**
1. Open app in Chrome/Edge
2. Wait for install prompt (or trigger from menu)
3. Click "Install"
4. **Expected:**
   - App installs as standalone PWA
   - Icon added to desktop/home screen
   - Opens in app window (no browser UI)

#### Test 6.2: Offline PWA Access
**Steps:**
1. Install PWA
2. Close browser completely
3. Disconnect from internet
4. Open PWA from desktop icon
5. **Expected:**
   - App opens successfully
   - Cached content loads
   - Full offline functionality

---

### 7. Integration Tests

#### Test 7.1: Complete User Journey
**Steps:**
1. **Register:** Create account as Teacher
2. **Activate:** Click email link, activate
3. **Login:** Sign in with Remember Me
4. **Navigate:** Browse courses, create assignment
5. **Offline:** Go offline, view data
6. **Action:** Try to update grade (queued)
7. **Online:** Go back online, sync action
8. **Logout:** Sign out successfully

**Expected:** All steps work smoothly end-to-end

#### Test 7.2: Multi-Device Sync
**Steps:**
1. Login on Device A
2. Create course/assignment
3. Go offline on Device A
4. Login on Device B
5. View same course/assignment
6. **Expected:** Data synced across devices

---

## Automated Test Commands

### Backend Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage
```

### Frontend Tests (if using Vitest)
```bash
# Run unit tests
npm run test

# Run with coverage
npm run test:coverage

# Run specific test
npm run test -- OfflineStorage.test.js
```

---

## Manual Testing Checklist

### Pre-Launch Checklist
- [ ] All registration flows work
- [ ] Email activation system functional
- [ ] Login with active/inactive accounts
- [ ] Remember Me checkbox works
- [ ] JWT tokens generated correctly
- [ ] API routes protected
- [ ] Offline mode detected
- [ ] Data cached in IndexedDB
- [ ] Pending actions queue correctly
- [ ] Online sync works automatically
- [ ] PWA installs successfully
- [ ] Service worker caches properly
- [ ] Token refresh works
- [ ] Logout clears all data

### Performance Checklist
- [ ] Initial page load < 3 seconds
- [ ] JWT validation < 500ms
- [ ] IndexedDB queries < 100ms
- [ ] Offline-to-online sync < 5 seconds
- [ ] No memory leaks in offline mode

### Security Checklist
- [ ] JWT tokens expire correctly
- [ ] Inactive accounts cannot login
- [ ] Activation tokens expire after 24hrs
- [ ] Passwords hashed properly
- [ ] API routes require authentication
- [ ] XSS protection enabled
- [ ] CSRF protection on forms

---

## Debugging Tips

### View JWT Token
```javascript
// In browser console
localStorage.getItem('elevategs_jwt_token')
// or
sessionStorage.getItem('elevategs_jwt_token')
```

### View IndexedDB Data
1. Open DevTools
2. Application tab
3. IndexedDB → ElevateGS_Offline
4. Inspect each object store

### View Service Worker
1. Open DevTools
2. Application tab → Service Workers
3. Check status, scope, and update

### View Pending Actions
```javascript
// In browser console
import offlineStorage from './offline-storage.js';
offlineStorage.getPendingActions().then(console.log);
```

### Clear Everything
```javascript
// Clear all offline data
indexedDB.deleteDatabase('ElevateGS_Offline');
localStorage.clear();
sessionStorage.clear();
caches.keys().then(names => names.forEach(name => caches.delete(name)));
```

---

## Common Issues & Solutions

### Issue: Offline banner won't disappear
**Solution:** Clear service worker cache, hard refresh (Ctrl+Shift+R)

### Issue: JWT token not working
**Solution:** Check token in DevTools → Application → Storage, verify expiration

### Issue: Pending actions not syncing
**Solution:** Check network tab for failed requests, verify API authentication

### Issue: PWA not installing
**Solution:** Ensure HTTPS enabled, check manifest.json, verify service worker registered

---

**Last Updated:** November 1, 2025
**Version:** 2.0 (with JWT & Offline Support)
