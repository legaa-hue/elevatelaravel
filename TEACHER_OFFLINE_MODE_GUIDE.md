# Teacher Offline Mode Implementation Guide

This guide explains how to implement comprehensive offline functionality for teachers in the ElevateGS LMS.

## Overview

The offline mode system allows teachers to:
- **Dashboard**: View last cached content when offline
- **Calendar**: Create/edit events offline, auto-sync when online
- **Class Record/Classwork**: Auto-download files for offline viewing
- **Reports**: View last cached reports offline
- **My Courses**: Create materials offline, auto-sync when online
- **People**: View cached student list when offline
- **Gradebook**: Edit tables offline, sync when online
- **Create Course**: Create courses offline, sync when online

## Core Components

### 1. Composables

#### `useOfflineSync.js`
Handles synchronization of pending actions when connection is restored.

**Key Functions:**
- `isOnline` - reactive connection status
- `isSyncing` - sync operation status
- `syncStatus` - sync result messages
- `pendingActionsCount` - number of pending offline changes
- `saveOfflineAction(type, data)` - save action for later sync
- `syncPendingActions()` - sync all pending actions
- `getCachedData(storeName, key)` - retrieve cached data
- `cacheData(storeName, data)` - save data to cache

#### `useTeacherOffline.js`
Teacher-specific offline operations.

**Key Functions:**
- `createCourseOffline(courseData)` - create course offline
- `createEventOffline(eventData)` - create calendar event offline
- `updateGradebookOffline(courseId, data)` - update gradebook offline
- `createMaterialOffline(materialData)` - create material offline
- `getCachedDashboard()` - get cached dashboard
- `getCachedCourses()` - get cached courses
- `getCachedEvents()` - get cached events

#### `useOfflineFiles.js`
Handles file downloading and caching.

**Key Functions:**
- `downloadFile(url, courseId)` - download and cache a file
- `downloadCourseFiles(courseId)` - download all course files
- `getCachedFile(url)` - get cached file
- `isFileCached(url)` - check if file is cached
- `downloadClassworkAttachments(classwork)` - auto-download attachments

### 2. Components

#### `OfflineSyncIndicator.vue`
Visual indicator showing:
- Offline status (cloud_off icon + pending count)
- Syncing status (spinning sync icon)
- Success message (check_circle + success message)
- Error message (error icon + error message)
- Pending changes badge

## Implementation Examples

### Dashboard Page

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import OfflineSyncIndicator from '@/components/OfflineSyncIndicator.vue';
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { ref, onMounted } from 'vue';

const props = defineProps({
    stats: Object,
    recentCourses: Array,
});

const { 
    isOnline, 
    syncPendingActions 
} = useOfflineSync();

const { 
    cacheDashboardData, 
    getCachedDashboard,
    getSmartData
} = useTeacherOffline();

const dashboardData = ref(null);
const dataSource = ref('online');

onMounted(async () => {
    // Cache current data if online
    if (isOnline.value && props.stats) {
        await cacheDashboardData({
            stats: props.stats,
            recentCourses: props.recentCourses,
            cached_at: Date.now()
        });
        dashboardData.value = props;
        dataSource.value = 'online';
    } else {
        // Load from cache if offline
        const cached = await getCachedDashboard();
        if (cached) {
            dashboardData.value = cached;
            dataSource.value = 'cache';
        }
    }
});
</script>

<template>
    <Head title="Teacher Dashboard" />
    <TeacherLayout>
        <!-- Sync Indicator -->
        <OfflineSyncIndicator @retry-sync="syncPendingActions" />
        
        <!-- Data source indicator -->
        <div v-if="dataSource === 'cache'" 
             class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <p class="text-sm text-yellow-700">
                📦 Showing cached data. Connect to internet for latest updates.
            </p>
        </div>
        
        <!-- Dashboard content -->
        <div v-if="dashboardData">
            <!-- Your dashboard content -->
        </div>
    </TeacherLayout>
</template>
```

### Calendar Page

```vue
<script setup>
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { useOfflineSync } from '@/composables/useOfflineSync';
import OfflineSyncIndicator from '@/components/OfflineSyncIndicator.vue';
import { ref } from 'vue';

const { isOnline } = useOfflineSync();
const { 
    createEventOffline, 
    updateEventOffline,
    deleteEventOffline,
    cacheEvents,
    getCachedEvents
} = useTeacherOffline();

const createEvent = async (eventData) => {
    if (!isOnline.value) {
        // Create offline
        const event = await createEventOffline(eventData);
        alert('✓ Event saved offline. Will sync when online.');
        return event;
    } else {
        // Create online normally
        return await fetch('/teacher/calendar/events', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        }).then(r => r.json());
    }
};

const updateEvent = async (eventId, eventData) => {
    if (!isOnline.value) {
        await updateEventOffline(eventId, eventData);
        alert('✓ Event updated offline. Will sync when online.');
    } else {
        // Update online
        await fetch(`/teacher/calendar/events/${eventId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        });
    }
};
</script>

<template>
    <div>
        <OfflineSyncIndicator />
        <!-- Calendar UI -->
    </div>
</template>
```

### Course Page (with auto file download)

```vue
<script setup>
import { useOfflineFiles } from '@/composables/useOfflineFiles';
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { onMounted, ref } from 'vue';

const props = defineProps({
    course: Object,
    classworks: Array
});

const { 
    downloadCourseFiles, 
    downloadClassworkAttachments,
    isFileCached 
} = useOfflineFiles();

const { createMaterialOffline } = useTeacherOffline();

const downloadingFiles = ref(false);

// Auto-download files when page loads (only if online)
onMounted(async () => {
    if (navigator.onLine) {
        downloadingFiles.value = true;
        await downloadCourseFiles(props.course.id);
        downloadingFiles.value = false;
    }
});

// Create material (works offline)
const createMaterial = async (materialData) => {
    if (!navigator.onLine) {
        const material = await createMaterialOffline({
            ...materialData,
            course_id: props.course.id
        });
        alert('✓ Material saved offline. Will sync when online.');
        return material;
    } else {
        // Normal online creation
        return await fetch('/teacher/classwork', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(materialData)
        }).then(r => r.json());
    }
};
</script>

<template>
    <div>
        <OfflineSyncIndicator />
        
        <!-- File download indicator -->
        <div v-if="downloadingFiles" class="bg-blue-50 p-4 mb-4 rounded">
            <p class="text-sm text-blue-700">
                📥 Downloading files for offline access...
            </p>
        </div>
        
        <!-- Course content -->
    </div>
</template>
```

### Gradebook Page

```vue
<script setup>
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { ref, watch } from 'vue';

const props = defineProps({
    course: Object,
    gradebook: Object
});

const { isOnline } = useOfflineSync();
const { updateGradebookOffline, getCachedGradebook } = useTeacherOffline();

const localGradebook = ref(props.gradebook);
const hasUnsavedChanges = ref(false);

// Watch for changes
watch(localGradebook, () => {
    hasUnsavedChanges.value = true;
}, { deep: true });

const saveGradebook = async () => {
    if (!isOnline.value) {
        // Save offline
        await updateGradebookOffline(props.course.id, localGradebook.value);
        hasUnsavedChanges.value = false;
        alert('✓ Gradebook saved offline. Will sync when online.');
    } else {
        // Save online
        await fetch(`/teacher/courses/${props.course.id}/gradebook`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(localGradebook.value)
        });
        hasUnsavedChanges.value = false;
    }
};
</script>

<template>
    <div>
        <OfflineSyncIndicator />
        
        <!-- Unsaved changes indicator -->
        <div v-if="hasUnsavedChanges && !isOnline" 
             class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex items-center">
                <span class="material-icons text-yellow-600 mr-2">cloud_off</span>
                <p class="text-sm text-yellow-700">
                    You have unsaved changes. They will be synced when you're online.
                </p>
            </div>
        </div>
        
        <!-- Gradebook table -->
        <button @click="saveGradebook" class="btn btn-primary">
            Save Gradebook
        </button>
    </div>
</template>
```

### Create Course Page

```vue
<script setup>
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const { isOnline } = useOfflineSync();
const { createCourseOffline } = useTeacherOffline();

const courseForm = ref({
    title: '',
    section: '',
    units: '',
    description: ''
});

const createCourse = async () => {
    if (!isOnline.value) {
        // Create offline
        const course = await createCourseOffline(courseForm.value);
        alert('✓ Course created offline. Will sync when online.');
        // Redirect to courses list
        router.visit('/teacher/courses');
    } else {
        // Create online normally
        router.post('/teacher/courses', courseForm.value);
    }
};
</script>

<template>
    <div>
        <OfflineSyncIndicator />
        
        <form @submit.prevent="createCourse">
            <!-- Form fields -->
            <button type="submit" class="btn btn-primary">
                {{ isOnline ? 'Create Course' : 'Create Course (Offline)' }}
            </button>
        </form>
    </div>
</template>
```

## Auto-Sync Behavior

The system automatically syncs pending actions when:
1. Connection is restored (offline → online)
2. Page loads and user is online
3. User manually clicks "retry sync" on pending actions indicator

## Visual Indicators

### Connection Status Icons
- `cloud_off` - Offline mode
- `sync` (spinning) - Syncing in progress
- `cloud_upload` - Pending changes waiting to sync
- `cloud_done` - All synced, online
- `check_circle` - Sync successful
- `error` - Sync failed

### Status Messages
- **Offline**: "You're offline - Changes will sync when online"
- **Syncing**: "Syncing... - Uploading offline changes"
- **Success**: "✓ Successfully synced X changes"
- **Error**: "✗ Sync failed. Will retry later."

## Best Practices

1. **Always cache data when online** to ensure offline access
2. **Show data source indicator** when displaying cached data
3. **Include OfflineSyncIndicator** on all teacher pages
4. **Auto-download files** for classwork and submissions
5. **Validate data before caching** to ensure quality
6. **Clear old cache periodically** to save space
7. **Test offline functionality** by toggling DevTools offline mode
8. **Provide clear feedback** to users about offline status

## Testing Offline Mode

### In Chrome DevTools:
1. Open DevTools (F12)
2. Go to Network tab
3. Select "Offline" from the throttling dropdown
4. Test creating/editing content
5. Go back online to see sync

### Tips:
- Check IndexedDB in Application tab to see stored data
- Monitor Service Worker in Application → Service Workers
- Clear cache using Application → Clear storage when testing

## File Storage Limits

- **IndexedDB**: ~50% of available disk space (varies by browser)
- **Service Worker Cache**: Configured to 50MB max per file
- **Total cache size**: Automatically managed by browser

## Troubleshooting

### Sync not working:
- Check browser console for errors
- Verify CSRF token is valid
- Check network connectivity
- Verify API endpoints are correct

### Files not downloading:
- Check storage permissions
- Verify file URLs are correct
- Check Service Worker is active
- Clear cache and retry

### Data not persisting:
- Check IndexedDB is enabled
- Verify storage quota not exceeded
- Check browser privacy settings

## Future Enhancements

- Background sync for large file uploads
- Conflict resolution for simultaneous edits
- Compression for cached data
- Selective sync options
- Offline analytics tracking
