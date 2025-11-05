# Push Notification Testing Guide

## ⚠️ IMPORTANT: Setup First

Before testing, you **MUST** generate VAPID keys:

```bash
php generate-vapid.php
```

Then add the output to your `.env` file:
```env
VAPID_PUBLIC_KEY=your_public_key_here
VAPID_PRIVATE_KEY=your_private_key_here
VAPID_SUBJECT=mailto:your-email@example.com
```

## 🧪 Testing Methods

### Method 1: Visual Test Page (Recommended)

1. **Build assets:**
   ```bash
   npm run build
   ```

2. **Visit test page:**
   ```
   http://localhost:8000/push-test
   ```

3. **Follow the steps:**
   - Click "Request Permission" → Allow notifications
   - Click "Subscribe" → Registers your browser
   - Click "Send Test Notification" → Receive a push!

### Method 2: Browser Console (Advanced)

Since you can't use `import` in the console, use the already-imported service:

1. **Open any page of your app**

2. **Open browser console (F12)**

3. **Access the service through window:**
   ```javascript
   // The service is available on any page with the app loaded
   
   // Check support
   console.log('Supported:', 'Notification' in window);
   
   // Check permission
   console.log('Permission:', Notification.permission);
   ```

4. **Use Vue DevTools instead:**
   - Install Vue DevTools extension
   - Open DevTools → Vue tab
   - Find a component
   - Access the service in console via the component

### Method 3: API Testing (Backend Only)

Test the backend endpoints directly:

```bash
# 1. Get the VAPID public key
curl http://localhost:8000/api/push/public-key

# 2. Subscribe (need valid subscription object from browser)
curl -X POST http://localhost:8000/api/push/subscribe \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "endpoint": "https://...",
    "keys": {
      "p256dh": "...",
      "auth": "..."
    }
  }'

# 3. Send test notification
curl -X POST http://localhost:8000/api/push/test \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🎯 Recommended: Use the Test Page

The easiest way is to use the test page at `/push-test`:

1. **No code needed** - Just click buttons
2. **Visual status** - See what's working/failing
3. **Step-by-step** - Guided through the process
4. **Real-time feedback** - Instant error messages
5. **Subscription details** - See your subscription info

## 📋 Testing Checklist

Before testing, verify:

- [ ] VAPID keys generated and in `.env`
- [ ] App running on HTTPS or localhost
- [ ] Service Worker registered (`/pwa-status` to check)
- [ ] Browser supports notifications (Chrome, Edge, Firefox)
- [ ] Notifications not blocked in browser settings
- [ ] Assets built (`npm run build`)

## 🔍 Troubleshooting

### "VAPID public key not configured"
```bash
# Run this:
php generate-vapid.php

# Copy output to .env
```

### "Service Worker not registered"
```bash
# Rebuild assets:
npm run build

# Check at:
http://localhost:8000/pwa-status
```

### "Permission denied"
- Check browser notification settings
- Try incognito/private mode
- Reset site permissions in browser

### "Subscription failed"
- Check browser console for errors
- Verify VAPID keys are correct
- Ensure you're on HTTPS or localhost

## 🎨 Testing UI Components

The test page (`/push-test`) shows:

- ✅ Browser support status
- ✅ Permission status (granted/denied/default)
- ✅ Subscription status (subscribed/not subscribed)
- ✅ Detailed subscription information
- ✅ Step-by-step action buttons
- ✅ Real-time success/error messages
- ✅ Troubleshooting tips

## 🚀 Quick Test Flow

```
1. php generate-vapid.php → Copy to .env
2. npm run build
3. Visit: http://localhost:8000/push-test
4. Click: Request Permission → Allow
5. Click: Subscribe → Success
6. Click: Send Test → Check notifications!
```

## 📱 Testing on Different Devices

### Desktop
- Chrome/Edge: Full support ✅
- Firefox: Full support ✅
- Safari 16+: Partial support ⚠️

### Mobile
- Android Chrome: Full support ✅
- iOS Safari 16.4+: Limited support ⚠️
- iOS Chrome: Uses Safari engine, same limitations

## 🔧 Integration Testing

After basic testing works, test in real scenarios:

1. **New Classwork Notification**
   ```php
   // In ClassworkController
   $user->notify(new \App\Notifications\ClassworkPosted($classwork));
   ```

2. **Grade Updated Notification**
   ```php
   // In GradebookController
   $student->notify(new \App\Notifications\GradeUpdated($grade));
   ```

3. **Assignment Due Reminder**
   ```php
   // In scheduled job
   $students->each(fn($s) => $s->notify(new AssignmentReminder($assignment)));
   ```

## 📊 Monitoring

Check push subscription data:

```sql
-- See all subscriptions
SELECT user_id, endpoint, created_at 
FROM push_subscriptions;

-- Count subscriptions per user
SELECT user_id, COUNT(*) as devices
FROM push_subscriptions
GROUP BY user_id;

-- Recent subscriptions
SELECT * FROM push_subscriptions
ORDER BY created_at DESC
LIMIT 10;
```

## ✅ Success Indicators

You'll know it's working when:

1. Permission prompt appears and you click "Allow"
2. Test page shows "✅ Subscribed"
3. Test notification appears in OS notification center
4. Clicking notification opens your app
5. Subscription endpoint saved in database

## 🎉 Next Steps

After testing works:

1. Add subscribe/unsubscribe UI to profile settings
2. Create notification classes for each event type
3. Integrate with NotificationService
4. Test on production with real HTTPS domain
5. Monitor subscription rates and delivery success
