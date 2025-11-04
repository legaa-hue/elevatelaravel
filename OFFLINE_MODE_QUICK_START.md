# Quick Start: Adding Offline Mode to Teacher Pages

## 5-Minute Integration Guide

### Step 1: Add Sync Indicator to Layout

If not already added, include the sync indicator in your teacher layout:

```vue
<!-- resources/js/Layouts/TeacherLayout.vue -->
<template>
    <div>
        <!-- Add this at the top level -->
        <OfflineSyncIndicator @retry-sync="handleRetrySync" />
        
        <!-- Rest of your layout -->
        <slot />
    </div>
</template>

<script setup>
import OfflineSyncIndicator from '@/components/OfflineSyncIndicator.vue';
import { useOfflineSync } from '@/composables/useOfflineSync';

const { syncPendingActions } = useOfflineSync();

const handleRetrySync = () => {
    syncPendingActions();
};
</script>
```

### Step 2: Add Offline Support to a Page

Example: Making the Dashboard offline-capable

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { useTeacherOffline } from '@/composables/useTeacherOffline';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { ref, onMounted } from 'vue';

const props = defineProps({
    stats: Object,
    recentCourses: Array,
});

const { isOnline } = useOfflineSync();
const { cacheDashboardData, getCachedDashboard } = useTeacherOffline();

const data = ref(props);
const isFromCache = ref(false);

onMounted(async () => {
    if (isOnline.value && props.stats) {
        // Cache data when online
        await cacheDashboardData({
            stats: props.stats,
            recentCourses: props.recentCourses,
            cached_at: Date.now()
        });
    } else if (!isOnline.value) {
        // Load from cache when offline
        const cached = await getCachedDashboard();
        if (cached) {
            data.value = cached;
            isFromCache.value = true;
        }
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <TeacherLayout>
        <!-- Show cache indicator -->
        <div v-if="isFromCache" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <p class="text-sm text-yellow-700">
                📦 Viewing cached data. Connect to internet for latest updates.
            </p>
        </div>
        
        <!-- Your dashboard content using data.stats, data.recentCourses -->
    </TeacherLayout>
</template>
```

### Step 3: Add Offline Create/Edit Functionality

Example: Creating a course offline

```vue
<script setup>
import { router } from '@inertiajs/vue3';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { useTeacherOffline } from '@/composables/useTeacherOffline';

const { isOnline } = useOfflineSync();
const { createCourseOffline } = useTeacherOffline();

const form = ref({
    title: '',
    section: '',
    units: ''
});

const submitForm = async () => {
    if (!isOnline.value) {
        // Handle offline
        await createCourseOffline(form.value);
        alert('✓ Course created offline. Will sync when online.');
        router.visit('/teacher/courses');
    } else {
        // Handle online (normal Inertia post)
        router.post('/teacher/courses', form.value);
    }
};
</script>

<template>
    <form @submit.prevent="submitForm">
        <input v-model="form.title" placeholder="Course Title" required />
        <input v-model="form.section" placeholder="Section" required />
        <input v-model="form.units" placeholder="Units" required />
        
        <button type="submit">
            {{ isOnline ? 'Create Course' : 'Create Course (Offline)' }}
        </button>
    </form>
</template>
```

### Step 4: Add Auto File Downloads

Example: Auto-download course materials

```vue
<script setup>
import { useOfflineFiles } from '@/composables/useOfflineFiles';
import { onMounted, ref } from 'vue';

const props = defineProps({
    course: Object,
    classworks: Array
});

const { downloadCourseFiles } = useOfflineFiles();
const isDownloading = ref(false);

onMounted(async () => {
    if (navigator.onLine) {
        isDownloading.value = true;
        try {
            const result = await downloadCourseFiles(props.course.id);
            console.log(`Downloaded ${result.count} files`);
        } catch (error) {
            console.error('Download error:', error);
        } finally {
            isDownloading.value = false;
        }
    }
});
</script>

<template>
    <div>
        <div v-if="isDownloading" class="bg-blue-50 p-3 rounded mb-4">
            <span class="material-icons animate-spin">sync</span>
            Downloading files for offline access...
        </div>
        
        <!-- Course content -->
    </div>
</template>
```

## Common Patterns

### Pattern 1: Cache Data on Load

```javascript
onMounted(async () => {
    if (isOnline.value) {
        await cacheData('storeName', props.data);
    }
});
```

### Pattern 2: Load Cache When Offline

```javascript
const loadData = async () => {
    if (!isOnline.value) {
        const cached = await getCachedData('storeName');
        if (cached) {
            data.value = cached;
            isFromCache.value = true;
        }
    }
};
```

### Pattern 3: Offline Create Operation

```javascript
const create = async (formData) => {
    if (!isOnline.value) {
        await createOfflineAction(formData);
        showNotification('Saved offline');
    } else {
        await createOnline(formData);
    }
};
```

### Pattern 4: Offline Edit Operation

```javascript
const update = async (id, formData) => {
    if (!isOnline.value) {
        await updateOfflineAction(id, formData);
        showNotification('Updated offline');
    } else {
        await updateOnline(id, formData);
    }
};
```

### Pattern 5: Display Offline Status

```vue
<div v-if="!isOnline" class="alert alert-warning">
    <span class="material-icons">cloud_off</span>
    You're offline. Changes will sync automatically.
</div>
```

## Available Composable Functions

### useOfflineSync()
- `isOnline` - Boolean reactive ref
- `isSyncing` - Boolean reactive ref
- `syncStatus` - Object with sync messages
- `pendingActionsCount` - Number of pending actions
- `saveOfflineAction(type, data)` - Queue an action
- `syncPendingActions()` - Manually trigger sync
- `getCachedData(store, key)` - Get cached data
- `cacheData(store, data)` - Cache data

### useTeacherOffline()
- `createCourseOffline(data)`
- `createEventOffline(data)`
- `createClassworkOffline(data)`
- `createMaterialOffline(data)`
- `updateGradebookOffline(courseId, data)`
- `getCachedDashboard()`
- `getCachedCourses()`
- `getCachedEvents()`
- `getCachedClasswork(courseId)`
- `getCachedGradebook(courseId)`
- `cacheData(store, data)`

### useOfflineFiles()
- `downloadFile(url, courseId)`
- `downloadCourseFiles(courseId)`
- `getCachedFile(url)`
- `isFileCached(url)`
- `downloadClassworkAttachments(classwork)`

## Testing Your Implementation

### Test Offline Mode:
1. Open Chrome DevTools (F12)
2. Go to Network tab
3. Select "Offline" from dropdown
4. Try creating/editing content
5. Switch back to "Online"
6. Watch sync happen automatically

### Test Cache:
1. Open DevTools → Application tab
2. Click IndexedDB → ElevateGS_Offline
3. View stored data in each store
4. Clear data to test fresh cache

### Test Service Worker:
1. DevTools → Application → Service Workers
2. Check status is "activated and running"
3. View cached resources in Cache Storage

## Troubleshooting

### Data not caching?
```javascript
// Check if storage initialized
console.log('DB initialized:', await offlineStorage.init());

// Check what's in cache
const data = await offlineStorage.getAll('courses');
console.log('Cached courses:', data);
```

### Sync not working?
```javascript
// Check pending actions
const pending = await offlineStorage.getPendingActions();
console.log('Pending actions:', pending);

// Manually trigger sync
await syncPendingActions();
```

### Files not downloading?
```javascript
// Check if file cached
const cached = await isFileCached('/storage/file.pdf');
console.log('File cached:', cached);

// Try manual download
await downloadFile('/storage/file.pdf', courseId);
```

## Best Practices

✅ **DO:**
- Cache data when online
- Show cache indicators
- Auto-download important files
- Provide clear offline messaging
- Test offline functionality regularly

❌ **DON'T:**
- Cache sensitive data long-term
- Download all files automatically (be selective)
- Assume sync will always succeed
- Hide offline status from user
- Cache without updating when online

## Need Help?

Refer to:
- `TEACHER_OFFLINE_MODE_GUIDE.md` - Full documentation
- `TEACHER_OFFLINE_IMPLEMENTATION_SUMMARY.md` - Feature overview
- Composable source files for detailed API

---

**Ready to implement!** Start with one page and expand from there.
