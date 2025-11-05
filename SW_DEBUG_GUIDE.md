# Service Worker Debug Guide

## Issue: Service Worker Not Registering

The Service Worker is not being found/registered. Let's debug this step by step.

## Step 1: Check if SW file exists

Open in browser:
```
http://127.0.0.1:8000/build/sw.js
```

You should see the Service Worker JavaScript code. If you get 404, the SW wasn't built correctly.

## Step 2: Check browser console on page load

Look for these messages when you load ANY page:
- ✅ Service Worker registered:
- ❌ Service Worker registration error:

If you don't see either, the SW is not being registered at all.

## Step 3: Check Application tab in DevTools

1. Press F12
2. Go to "Application" tab
3. Click "Service Workers" in left sidebar
4. Should show a service worker for `http://127.0.0.1:8000`

If empty, SW never registered.

## Step 4: Manual check in console

Paste this in browser console:
```javascript
navigator.serviceWorker.getRegistration().then(reg => {
  console.log('SW Registration:', reg);
  if (reg) {
    console.log('SW Active:', reg.active);
    console.log('SW Installing:', reg.installing);
    console.log('SW Waiting:', reg.waiting);
  }
});
```

## Step 5: Check for errors

Look for errors like:
- CORS errors
- HTTPS requirement (should be OK on localhost)
- Browser blocking SW

## Quick Fix: Hard Reset

1. Open DevTools (F12)
2. Go to Application tab
3. Click "Clear storage" in left sidebar
4. Check "Unregister service workers"
5. Click "Clear site data"
6. Close DevTools
7. Hard refresh (Ctrl + Shift + R)
8. Check console for SW registration message

## If Still Not Working

The VitePWA might not be registering the SW properly. Try:

1. Check vite.config.js has VitePWA plugin
2. Check public/build/sw.js exists
3. Try accessing http://127.0.0.1:8000/build/sw.js directly
4. Check for any build errors

## Next Steps

Please check Step 1 first - does `/build/sw.js` load in your browser?
