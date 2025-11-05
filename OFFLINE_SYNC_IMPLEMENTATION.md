# Offline Navigation & Sync Implementation Guide

## Overview
This guide documents the implementation of offline-first functionality for ElevateGS, including page navigation while offline and automatic syncing of teacher changes (materials and gradebook) when connection is restored.

## What Was Fixed

### 1. Offline Page Navigation
**Problem**: Users couldn't navigate between pages when offline.

**Solution**: Added page HTML caching to the Workbox configuration in `vite.config.js`:

```javascript
{
    urlPattern: /^.*\/(teacher|student|admin)\/(dashboard|courses|classwork|calendar|gradebook|reports|people|progress).*/i,
    handler: 'NetworkFirst',
    options: {
        cacheName: 'pages-cache-v1',
        networkTimeoutSeconds: 3,
        expiration: {
            maxEntries: 50,
            maxAgeSeconds: 60 * 60 // 1 hour
        }
    }
}
```

This caches all main app pages so they're available offline for navigation.

### 2. Offline Data Sync
**Problem**: Teacher changes to materials and gradebook weren't saved when offline.

**Solution**: Implemented a comprehensive offline sync system using IndexedDB.

## New Files Created

### 1. `resources/js/offline-sync.js`
Main offline sync service that:
- Stores pending API requests in IndexedDB
- Queues offline materials creation
- Queues offline gradebook changes
- Automatically syncs when connection restored
- Handles background sync via Service Worker

### 2. `resources/js/composables/useOfflineSync.js`
Vue composable providing offline-first functionality:
- `createMaterialOffline()` - Create materials with offline fallback
- `updateGradeOffline()` - Update grades with offline fallback
- `syncNow()` - Manually trigger sync
- `isOnline` - Online status reactive ref
- `pendingChanges` - Count of pending syncs

### 3. `resources/js/Components/OfflineSyncIndicator.vue`
Visual indicator showing:
- Syncing status (blue spinner)
- Pending changes count (yellow clock)
- All synced (green checkmark)

## How It Works

### Offline Materials Creation
When a teacher creates materials offline:

1. Material data saved to IndexedDB `offline-materials` store
2. API request queued in `pending-requests` store
3. Temporary ID assigned for local reference
4. When online, queued request sent to server
5. On success, offline copy removed
6. User notified of successful sync

### Offline Gradebook Changes
When a teacher updates grades offline:

1. Grade change saved to IndexedDB `offline-grades` store
2. API request queued in `pending-requests` store
3. Grade displayed with "pending sync" indicator
4. When online, queued request sent to server
5. On success, offline copy marked as synced
6. User notified of successful sync

### Automatic Sync Triggers
Sync happens automatically when:
- App starts and connection is available
- Browser goes from offline to online
- Background Sync API triggers (when available)
- Every time user navigates to a page (checks for pending items)

## Usage in Components

### Example: Create Material with Offline Support

```vue
<script setup>
import { useOfflineSync } from '@/composables/useOfflineSync';

const { createMaterialOffline, isOnline, pendingChanges } = useOfflineSync();

const createMaterial = async () => {
    try {
        const material = await createMaterialOffline(courseId, {
            title: 'New Material',
            type: 'PDF',
            file: fileData
        });
        
        if (material.createdOffline) {
            alert('Saved offline. Will sync when connection restored.');
        } else {
            alert('Material created successfully!');
        }
    } catch (error) {
        console.error('Failed to create material:', error);
    }
};
</script>

<template>
    <button @click="createMaterial">
        Create Material
        <span v-if="!isOnline">(Offline)</span>
    </button>
    <div v-if="pendingChanges > 0">
        {{ pendingChanges }} change(s) pending sync
    </div>
</template>
```

### Example: Update Grade with Offline Support

```vue
<script setup>
import { useOfflineSync } from '@/composables/useOfflineSync';

const { updateGradeOffline, isOnline } = useOfflineSync();

const updateGrade = async (studentId, gradeType, value) => {
    try {
        const result = await updateGradeOffline(courseId, studentId, gradeType, value);
        
        if (result.offline) {
            alert('Grade saved offline. Will sync when connection restored.');
        } else {
            alert('Grade updated successfully!');
        }
    } catch (error) {
        console.error('Failed to update grade:', error);
    }
};
</script>
```

## Installation Steps

### 1. Install Dependencies
```bash
npm install
```

This will install the `idb` package (IndexedDB wrapper) added to package.json.

### 2. Rebuild Assets
```bash
npm run build
```

This compiles:
- Updated vite.config.js with page caching
- New offline-sync.js service
- Updated app.js with sync initialization
- Updated service worker with background sync

### 3. Deploy to Production
Upload the following to Hostinger:

**Files to update**:
- `public/build/` - New compiled assets
- `resources/js/offline-sync.js` - New file
- `resources/js/app.js` - Updated
- `resources/js/composables/useOfflineSync.js` - New file
- `public/sw-push.js` - Updated
- `vite.config.js` - Updated
- `package.json` - Updated

### 4. Clear Browser Cache
Users should clear their browser cache or hard refresh (Ctrl+Shift+R) to get the new service worker.

## Testing Offline Functionality

### Test Offline Navigation
1. Open the app and log in
2. Navigate to a few pages (dashboard, courses, gradebook)
3. Open DevTools → Network tab
4. Click "Offline" to simulate offline mode
5. Try navigating between pages - should work!

### Test Offline Material Creation
1. Go to a course as a teacher
2. Open DevTools → Network → Click "Offline"
3. Try to create a new material
4. Should save with "pending sync" message
5. Go back online
6. Material should automatically sync

### Test Offline Gradebook
1. Go to gradebook as a teacher
2. Go offline (DevTools → Network → Offline)
3. Change a student's grade
4. Should save with "pending sync" indicator
5. Go back online
6. Grade should automatically sync

## Monitoring & Debugging

### Check Pending Syncs
Open browser console and run:
```javascript
offlineSync.getPendingRequests().then(console.log)
```

### Check Offline Materials
```javascript
offlineSync.getOfflineMaterials(courseId).then(console.log)
```

### Check Offline Grades
```javascript
offlineSync.getOfflineGrades(courseId).then(console.log)
```

### Force Sync
```javascript
offlineSync.syncAll()
```

### Clear All Offline Data (use with caution)
```javascript
offlineSync.clearAll()
```

## Database Schema (IndexedDB)

### Store: `pending-requests`
- `id` (auto-increment) - Request ID
- `type` - Request type ('create-material', 'update-grade')
- `method` - HTTP method ('POST', 'PUT', etc.)
- `url` - API endpoint
- `data` - Request body
- `metadata` - Additional data (tempId, courseId, etc.)
- `timestamp` - When queued
- `synced` - Boolean, true when synced
- `syncedAt` - When synced

### Store: `offline-materials`
- `tempId` - Temporary ID (primary key)
- `courseId` - Course ID
- `title`, `type`, `description`, etc. - Material data
- `createdOffline` - Boolean flag
- `timestamp` - When created

### Store: `offline-grades`
- `id` (auto-increment) - Grade change ID
- `courseId` - Course ID
- `studentId` - Student ID
- `gradeType` - Type of grade
- `value` - Grade value
- `timestamp` - When changed
- `synced` - Boolean, true when synced

## Security Considerations

- All offline data stored in browser's IndexedDB (local only)
- Data automatically synced with server when online
- CSRF tokens included in all sync requests
- Authentication required for all API endpoints
- Offline data cleared after successful sync (materials) or marked as synced (grades)

## Performance

- IndexedDB operations are asynchronous (non-blocking)
- Sync runs in background, doesn't block UI
- Page caching improves load time
- Maximum 50 pages cached (LRU eviction)
- Old synced requests cleaned up after 24 hours

## Future Enhancements

Potential improvements:
- Conflict resolution for concurrent edits
- Differential sync (only send changes)
- Optimistic UI updates
- Offline file uploads with progress
- Sync retry with exponential backoff
- User-initiated sync button
- Detailed sync history

## Troubleshooting

### Pages Don't Load Offline
- Check if service worker is registered (DevTools → Application → Service Workers)
- Clear cache and reload
- Check console for errors

### Sync Doesn't Happen
- Check browser console for errors
- Verify navigator.onLine is true
- Check pending requests: `offlineSync.getPendingRequests()`
- Manually trigger sync: `offlineSync.syncAll()`

### Duplicate Syncs
- This shouldn't happen, but if it does:
- Each request has a unique ID and is marked as synced after success
- Check for errors in sync process

## Support

For issues or questions:
1. Check browser console for errors
2. Verify all files were updated correctly
3. Clear browser cache
4. Test in incognito mode
5. Check network tab in DevTools
