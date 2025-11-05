# 🧪 Offline Button Testing Checklist

## Quick Test (2 minutes)

1. **Hard Refresh**
   - [ ] Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
   - [ ] Wait for page to fully load

2. **Check Service Worker**
   - [ ] Open DevTools (F12)
   - [ ] Go to **Application** tab
   - [ ] Click **Service Worker** in sidebar
   - [ ] Verify: `sw.js` is "activated and is running"
   - [ ] Verify: Status shows green dot ●

3. **Go Offline**
   - [ ] In DevTools, go to **Network** tab
   - [ ] Check **Offline** checkbox at top
   - [ ] Page should still be visible

4. **Test Buttons!**
   - [ ] Click any navigation link → Should work ✅
   - [ ] Click any button → Should work ✅
   - [ ] Try form submissions → Should queue offline ✅

---

## Full Test (10 minutes)

### Part 1: Student Workflow
- [ ] Navigate to `/student/courses` (while online)
- [ ] Open a course
- [ ] Go offline (DevTools → Network → Offline)
- [ ] Click "View Course" button → Should work!
- [ ] Click "Submit Assignment" → Should show offline message
- [ ] Navigate to Dashboard → Should work!
- [ ] Check Console → No errors

### Part 2: Teacher Workflow
- [ ] Navigate to `/teacher/courses` (while online)
- [ ] Open a course
- [ ] Go offline
- [ ] Click "View Submissions" → Should work!
- [ ] Click "Grade Student" → Should work!
- [ ] Navigate to Calendar → Should work!
- [ ] Check Console → No errors

### Part 3: Cache Verification
- [ ] DevTools → Application → Cache Storage
- [ ] Verify these caches exist:
  - [ ] `workbox-precache-v2-...` (72 files)
  - [ ] `js-modules-cache-v1` (should populate after navigation)
  - [ ] `css-cache-v1` (should populate after navigation)
  - [ ] `workbox-runtime-v1`
  - [ ] `pages-cache-v1`
  - [ ] `images-cache-v1`

### Part 4: Offline Submission Test
- [ ] Still offline
- [ ] Try to submit classwork
- [ ] Should see: "📴 Submission saved offline"
- [ ] Check IndexedDB → `elevategs-offline-sync` → `offline-submissions`
- [ ] Go back online
- [ ] Submission should auto-sync
- [ ] Check server → Submission received!

---

## 🐛 Common Issues & Fixes

### Issue: Old Service Worker Still Active

**Symptoms:**
- Hard refresh doesn't help
- Still see old caches
- Buttons don't work offline

**Fix:**
```
1. DevTools → Application → Storage
2. Click "Clear site data"
3. Hard refresh (Ctrl + Shift + R)
4. Service Worker should re-register
```

---

### Issue: "Failed to fetch" Errors

**Symptoms:**
- Console shows 404 for .js files
- Buttons don't respond
- Page looks broken offline

**Fix:**
```
1. Check Network tab while online
2. Look for failed .js requests
3. If any fail → Run npm run build again
4. Copy new sw.js and workbox-*.js to public/
5. Hard refresh
```

---

### Issue: Buttons Work Online But Not Offline

**Symptoms:**
- Everything works with internet
- Go offline → clicks do nothing
- No console errors

**Fix:**
```
1. Open DevTools → Application → Cache Storage
2. Check js-modules-cache-v1
3. If empty or missing:
   a. Go back online
   b. Navigate to a few pages (populate cache)
   c. Go offline again
   d. Try buttons again
```

---

### Issue: Service Worker Stuck in "Waiting"

**Symptoms:**
- SW shows "waiting to activate"
- Never becomes active
- Old SW still in control

**Fix:**
```
1. DevTools → Application → Service Workers
2. Click "skipWaiting" button next to waiting SW
OR
3. Close ALL tabs with the app
4. Reopen → New SW should activate
OR
5. Check the box "Update on reload"
6. Hard refresh
```

---

## 📊 Expected Results

### ✅ Success Indicators

1. **Service Worker Status**
   - Status: "activated and is running"
   - Green dot indicator
   - No errors in console

2. **Cache Contents**
   - Precache: 72 entries
   - JS cache: 50+ entries (after navigation)
   - CSS cache: 10+ entries (after navigation)

3. **Offline Functionality**
   - All buttons clickable
   - Navigation works
   - Forms queue offline
   - No 404 errors

4. **Console Output (Online)**
   ```
   ✅ Offline storage initialized
   ✅ Auth token validated
   ✅ Offline sync initialized
   ✅ Service Worker registered
   ✅ SW scope: /
   ```

5. **Console Output (Going Offline)**
   ```
   📴 Connection lost - entering offline mode
   ```

6. **Console Output (Coming Online)**
   ```
   🌐 Connection restored! Auto-syncing...
   ✅ Synced 3 pending submissions
   ```

---

## 🎯 Final Verification

Run this in DevTools Console while **offline**:

```javascript
// Check if Service Worker is active
console.log('SW Active:', navigator.serviceWorker.controller !== null);

// Check cache
caches.keys().then(keys => {
    console.log('Available Caches:', keys);
    keys.forEach(key => {
        caches.open(key).then(cache => {
            cache.keys().then(requests => {
                console.log(`${key}: ${requests.length} entries`);
            });
        });
    });
});

// Check if online
console.log('Online Status:', navigator.onLine);

// Check IndexedDB
indexedDB.databases().then(dbs => {
    console.log('IndexedDB Databases:', dbs.map(db => db.name));
});
```

**Expected Output:**
```
SW Active: true
Online Status: false
Available Caches: (8) [
    "workbox-precache-v2-...",
    "js-modules-cache-v1",
    "css-cache-v1",
    "workbox-runtime-v1",
    "pages-cache-v1",
    "api-cache-v1",
    "images-cache-v1",
    "static-assets-cache-v1"
]
workbox-precache-v2-...: 72 entries
js-modules-cache-v1: 68 entries
css-cache-v1: 12 entries
...
IndexedDB Databases: ["elevategs-offline-sync"]
```

---

## ✅ Test Complete!

If all tests pass:
- ✅ Offline buttons work
- ✅ Navigation works offline
- ✅ Forms queue offline
- ✅ Auto-sync works

**Your PWA is production-ready! 🎉**

---

**Test Date:** _______________  
**Tester:** _______________  
**Status:** ☐ Pass  ☐ Fail  
**Notes:** ______________________________________
