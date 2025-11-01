# Push Notification Implementation Guide

## 🔔 Overview

ElevateGS now includes a **complete push notification system** that allows users to receive real-time notifications even when the app is not open.

---

## ✅ What's Been Implemented

### Backend Components

#### 1. **Database**
- ✅ Migration: `2025_11_01_060032_create_push_subscriptions_table.php`
- Table stores user push subscriptions with endpoints and encryption keys

#### 2. **Models**
- ✅ `User` model updated with `HasPushSubscriptions` trait
- Enables relationship: `$user->pushSubscriptions()`

#### 3. **Controllers**
- ✅ `PushNotificationController.php`
  - `POST /api/push/subscribe` - Subscribe to notifications
  - `POST /api/push/unsubscribe` - Unsubscribe from notifications
  - `GET /api/push/public-key` - Get VAPID public key
  - `POST /api/push/test` - Send test notification

#### 4. **Notifications**
- ✅ `TestPushNotification.php` - For testing push notifications
- ✅ `NewClassworkNotification.php` - Example: notify students of new assignments

#### 5. **Configuration**
- ✅ `config/webpush.php` - WebPush configuration
- ⚠️ Requires VAPID keys in `.env`

#### 6. **Routes**
- ✅ API routes registered in `routes/api.php`

### Frontend Components

#### 1. **Service**
- ✅ `resources/js/push-notification-service.js`
  - Singleton service for managing push subscriptions
  - Methods: `subscribe()`, `unsubscribe()`, `getSubscription()`, `sendTestNotification()`

#### 2. **Vue Component**
- ✅ `resources/js/Components/PushNotificationManager.vue`
  - Permission request banner
  - Notification status toggle
  - Test notification button (dev mode)

#### 3. **Service Worker**
- ✅ `public/sw-push.js`
  - Handles push events
  - Notification click handling
  - Background sync support

#### 4. **Package**
- ✅ Installed: `laravel-notification-channels/webpush` v10.2.0

---

## 🔧 Setup Instructions

### Step 1: Add VAPID Keys to `.env`

**Option A: Use Placeholder Keys (Development Only)**
```env
# Generated placeholder keys (already created)
VAPID_PUBLIC_KEY=MwXKYNF0SJY9S6_fYxZK7YtBDuFbK6ZSKtVOsUe8jbhDaIIPuWxq8d9R8PSOqeMBt8bTshJfHdp2YhxbKrc8tgg
VAPID_PRIVATE_KEY=wICuE6CNdCLIzYaoZ7tW_epjhMMhkNb22qtvIsWI2UU
VAPID_SUBJECT=mailto:admin@elevategs.com
```

**Option B: Generate Proper VAPID Keys (Production)**
```bash
# Visit this website to generate proper VAPID keys:
# https://web-push-codelab.glitch.me/

# Or use the Laravel package command (requires OpenSSL):
php artisan webpush:vapid
```

### Step 2: Update Service Worker

Add push notification handlers to your service worker by importing the custom file:

**Option A: Update `vite.config.js`** (if using Vite PWA)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            // ... existing config ...
            workbox: {
                // Import custom push notification handlers
                importScripts: ['/sw-push.js'],
            },
        }),
    ],
});
```

**Option B: Manual Import in Service Worker**
```javascript
// In your service worker file
importScripts('/sw-push.js');
```

### Step 3: Add Component to Dashboard

**For All Users (Teacher/Student):**

Edit `resources/js/Pages/Dashboard.vue` (or equivalent):
```vue
<template>
    <AuthenticatedLayout>
        <!-- Existing dashboard content -->
        
        <!-- Add Push Notification Banner -->
        <PushNotificationManager :show-banner="true" />
        
        <!-- Rest of dashboard -->
    </AuthenticatedLayout>
</template>

<script setup>
import PushNotificationManager from '@/Components/PushNotificationManager.vue';
// ... other imports
</script>
```

**For Settings/Profile Page:**
```vue
<template>
    <div class="settings-page">
        <h2>Notification Settings</h2>
        
        <!-- Add Push Notification Status -->
        <PushNotificationManager 
            :show-banner="false" 
            :show-status-indicator="true" 
        />
    </div>
</template>
```

### Step 4: Enable HTTPS (Required for Production)

Push notifications **require HTTPS** to work in production. Options:

1. **Local Development:**
   ```bash
   # Use Laravel Valet (macOS)
   valet secure elevategs
   
   # Or use Laravel Sail with SSL
   sail artisan serve --ssl
   ```

2. **Production:**
   - Use a reverse proxy (Nginx, Apache) with SSL certificate
   - Use Let's Encrypt for free SSL
   - Deploy to platforms with built-in SSL (Vercel, Netlify, AWS)

### Step 5: Test the Implementation

```bash
# 1. Start the server
php artisan serve

# 2. Start queue worker (for sending notifications)
php artisan queue:work

# 3. Start frontend
npm run dev

# 4. Open browser
# Navigate to: http://localhost:8000 (or https:// if SSL enabled)

# 5. Login to your account

# 6. You should see the notification permission banner

# 7. Click "Enable" to subscribe to push notifications

# 8. Test by clicking "Send Test Notification" (in dev mode)
```

---

## 📱 How to Use in Your App

### Send Notification When Creating Classwork

Edit `app/Http/Controllers/Teacher/ClassworkController.php`:

```php
use App\Notifications\NewClassworkNotification;

public function store(Request $request, Course $course)
{
    // Create classwork
    $classwork = $course->classworks()->create($request->validated());
    
    // Notify all students in the course
    $students = $course->students;
    
    foreach ($students as $student) {
        $student->notify(new NewClassworkNotification($classwork, $course->name));
    }
    
    return redirect()->route('teacher.courses.show', $course)
        ->with('success', 'Assignment created and students notified!');
}
```

### Send Notification for Grade Updates

Create `app/Notifications/GradeUpdatedNotification.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class GradeUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $submission;
    protected $classwork;

    public function __construct($submission, $classwork)
    {
        $this->submission = $submission;
        $this->classwork = $classwork;
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Grade Updated: ' . $this->classwork->title)
            ->body('Your submission has been graded: ' . $this->submission->grade . '%')
            ->icon('/build/assets/icon-192x192.png')
            ->data([
                'url' => route('student.submissions.show', $this->submission),
            ])
            ->tag('grade-' . $this->submission->id)
            ->requireInteraction(true);
    }
}
```

Then use it:

```php
// In GradeController
$student->notify(new GradeUpdatedNotification($submission, $classwork));
```

---

## 🔍 Testing

### Test Notification from Browser Console

```javascript
// Subscribe to notifications
await pushNotificationService.subscribe();

// Send test notification
await pushNotificationService.sendTestNotification(
    'Hello from Console!',
    'This is a test notification'
);
```

### Test from Backend

```php
// In Tinker or Controller
php artisan tinker

$user = User::find(1);
$user->notify(new \App\Notifications\TestPushNotification(
    'Hello from Laravel!',
    'This is a backend test notification',
    route('dashboard')
));
```

### Test API Endpoint

```bash
# Get public key
curl http://localhost:8000/api/push/public-key

# Send test notification (requires JWT token)
curl -X POST http://localhost:8000/api/push/test \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "API Test",
    "body": "Testing from API"
  }'
```

---

## 🐛 Troubleshooting

### Notifications Not Appearing

1. **Check Permission:**
   ```javascript
   console.log(Notification.permission); // Should be "granted"
   ```

2. **Check Subscription:**
   ```javascript
   const subscription = await pushNotificationService.getSubscription();
   console.log(subscription); // Should not be null
   ```

3. **Check Browser Support:**
   ```javascript
   console.log('serviceWorker' in navigator); // Should be true
   console.log('PushManager' in window); // Should be true
   ```

4. **Check Queue Worker:**
   ```bash
   # Make sure queue worker is running
   php artisan queue:work
   
   # Check failed jobs
   php artisan queue:failed
   ```

5. **Check VAPID Keys:**
   ```bash
   # Verify keys are in .env
   echo $VAPID_PUBLIC_KEY
   echo $VAPID_PRIVATE_KEY
   ```

### HTTPS Required Error

Push notifications require HTTPS in production. For local testing:
- Use `localhost` (works without HTTPS)
- Or set up local SSL certificate

### Browser-Specific Issues

- **Firefox:** Works with HTTPS only
- **Chrome:** Works on localhost without HTTPS, requires HTTPS otherwise
- **Safari:** Requires HTTPS and user gesture
- **Mobile:** Requires app to be "installed" (Add to Home Screen)

---

## 📊 Feature Matrix

| Feature | Status | Notes |
|---------|--------|-------|
| Subscribe to notifications | ✅ Complete | `/api/push/subscribe` |
| Unsubscribe from notifications | ✅ Complete | `/api/push/unsubscribe` |
| Send test notification | ✅ Complete | `/api/push/test` |
| Permission request UI | ✅ Complete | Banner component |
| Notification click handling | ✅ Complete | Opens app to URL |
| Background notifications | ✅ Complete | Works when app closed |
| Multiple device support | ✅ Complete | Each device has unique subscription |
| Notification actions | ✅ Complete | View/Close buttons |
| Rich notifications | ✅ Complete | Title, body, icon, image |
| Vibration patterns | ✅ Complete | Customizable vibration |

---

## 🚀 Production Checklist

- [ ] Generate proper VAPID keys (not placeholder)
- [ ] Add VAPID keys to production `.env`
- [ ] Enable HTTPS on production server
- [ ] Test notifications on multiple browsers
- [ ] Test on mobile devices (iOS/Android)
- [ ] Set up monitoring for failed notifications
- [ ] Configure notification retry logic
- [ ] Add analytics for notification engagement
- [ ] Create opt-in flow for users
- [ ] Add unsubscribe link in notification settings

---

## 📚 Additional Resources

- [Web Push Protocol](https://developers.google.com/web/fundamentals/push-notifications)
- [Laravel WebPush Package](https://github.com/laravel-notification-channels/webpush)
- [VAPID Key Generator](https://web-push-codelab.glitch.me/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)

---

## ✅ Summary

Your push notification system is now **fully implemented** and ready for testing!

**Next Steps:**
1. Add VAPID keys to `.env`
2. Add `PushNotificationManager` component to dashboard
3. Enable HTTPS for production
4. Start queue worker
5. Test the system!

🎉 **Congratulations!** You now have a complete push notification system!
