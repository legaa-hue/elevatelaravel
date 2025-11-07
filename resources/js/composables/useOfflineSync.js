import { ref, onMounted, onUnmounted, computed } from 'vue';
import offlineStorage from '../offline-storage';
import { router } from '@inertiajs/vue3';

// Module-scoped guards to avoid duplicate listeners and concurrent syncs across multiple component instances
let listenersInitialized = false;
let globalIsSyncing = false;

export function useOfflineSync() {
    const isOnline = ref(navigator.onLine);
    const isSyncing = ref(false);
    const syncStatus = ref(null);
    const pendingActionsCount = ref(0);
    const lastSyncTime = ref(null);
    const syncProgress = ref({ current: 0, total: 0 });
    const pendingActions = ref([]);

    // Update online status
    const updateOnlineStatus = () => {
        const wasOffline = !isOnline.value;
        isOnline.value = navigator.onLine;
        
        // If just came online, trigger sync
        if (wasOffline && isOnline.value) {
            console.log('🌐 Connection restored, starting sync...');
            syncPendingActions();
        }
    };

    // Sync pending actions when coming online
    const syncPendingActions = async () => {
        if (!isOnline.value || isSyncing.value || globalIsSyncing) return;

        try {
            isSyncing.value = true;
            globalIsSyncing = true;
            syncStatus.value = { type: 'syncing', message: 'Syncing data...' };

            const actions = await offlineStorage.getPendingActions();

            if (actions.length === 0) {
                isSyncing.value = false;
                syncProgress.value = { current: 0, total: 0 };
                return;
            }

            // Initialize progress
            syncProgress.value = { current: 0, total: actions.length };

            console.log(`🔄 Syncing ${actions.length} pending actions...`);

            let successCount = 0;
            let failCount = 0;
            const failedActions = [];
            // Track if current page should refresh specific props after sync
            let shouldReloadClassworks = false;
            let shouldReloadGradebook = false;

            for (const action of actions) {
                try {
                    await processPendingAction(action);
                    await offlineStorage.markActionSynced(action.id);
                    successCount++;
                    syncProgress.value.current++;

                    // If any action affects classworks/materials, mark for reload
                    if ([
                        'create_classwork',
                        'update_classwork',
                        'create_material',
                        'submit_classwork'
                    ].includes(action.type)) {
                        shouldReloadClassworks = true;
                    }
                    if (action.type === 'update_gradebook') {
                        shouldReloadGradebook = true;
                    }
                } catch (error) {
                    console.error('Failed to sync action:', action, error);
                    failCount++;
                    syncProgress.value.current++;
                    failedActions.push({ action, error: error.message });
                }
            }

            lastSyncTime.value = new Date().toISOString();
            
            if (failCount === 0) {
                syncStatus.value = {
                    type: 'success',
                    message: `✓ Successfully synced ${successCount} ${successCount === 1 ? 'change' : 'changes'}`
                };
            } else {
                syncStatus.value = {
                    type: 'warning',
                    message: `⚠ Synced ${successCount} changes, ${failCount} failed`
                };
            }

            // Clear status after 5 seconds
            setTimeout(() => {
                syncStatus.value = null;
            }, 5000);

            // Update pending count
            await updatePendingCount();

            // If we created/updated classworks/materials, refresh the page data so
            // new items appear without manual reload and progress widgets update.
            // Use partial reload for performance.
            if (shouldReloadClassworks) {
                try {
                    router.reload({ only: ['classworks', 'students'] });
                    // Also dispatch a lightweight global event for any listeners (same-tab only)
                    try {
                        const evt = new CustomEvent('app:classworks-updated', {
                            detail: { at: Date.now(), scope: 'classworks' }
                        });
                        window.dispatchEvent(evt);
                    } catch (e2) { /* ignore */ }
                } catch (e) {
                    // If router context isn't ready for some reason, ignore gracefully
                    console.warn('Could not trigger Inertia reload after sync', e);
                }
            }
            // If gradebook was updated, refresh gradebook-related props
            if (shouldReloadGradebook) {
                try {
                    router.reload({ only: ['gradebook', 'students'] });
                    try {
                        const evt = new CustomEvent('app:gradebook-updated', {
                            detail: { at: Date.now(), scope: 'gradebook' }
                        });
                        window.dispatchEvent(evt);
                    } catch (e2) { /* ignore */ }
                } catch (e) {
                    console.warn('Could not trigger Inertia reload after gradebook sync', e);
                }
            }
            
        } catch (error) {
            console.error('Sync error:', error);
            syncStatus.value = {
                type: 'error',
                message: '✗ Sync failed. Will retry later.'
            };
            setTimeout(() => {
                syncStatus.value = null;
            }, 5000);
        } finally {
            isSyncing.value = false;
            globalIsSyncing = false;
        }
    };

    // Process individual pending action
    const processPendingAction = async (action) => {
        const { type, data, endpoint, method = 'POST' } = action;

        switch (type) {
            case 'create_course':
                return await syncCreateCourse(data);
            
            case 'update_course':
                return await syncUpdateCourse(data);
            
            case 'create_event':
                return await syncCreateEvent(data);
            
            case 'update_event':
                return await syncUpdateEvent(data);
            
            case 'delete_event':
                return await syncDeleteEvent(data);
            
            case 'create_classwork':
                return await syncCreateClasswork({ ...data, pendingActionId: action.id });
            
            case 'update_classwork':
                return await syncUpdateClasswork(data);
            
            case 'create_material':
                return await syncCreateMaterial(data);
            
            case 'submit_classwork':
                return await syncSubmitClasswork({ ...data, pendingActionId: action.id });
            
            case 'update_gradebook':
                return await syncUpdateGradebook(data);
            
            case 'grade_submission':
                // If a custom endpoint was provided when saving the action, prefer it
                if (endpoint) {
                    return await syncCustomAction(endpoint, method, data);
                }
                return await syncGradeSubmission(data);
            
            case 'custom':
                return await syncCustomAction(endpoint, method, data);
            
            default:
                throw new Error(`Unknown action type: ${type}`);
        }
    };

    // -----------------------------
    // Pending uploads (files) store
    // -----------------------------
    const openPendingUploadsDB = () => {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('PendingUploadsDB', 1);
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains('pendingFiles')) {
                    const store = db.createObjectStore('pendingFiles', { keyPath: 'id', autoIncrement: true });
                    store.createIndex('actionId', 'actionId', { unique: false });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    };

    const savePendingFiles = async (actionId, files) => {
        if (!files || files.length === 0) return;
        const db = await openPendingUploadsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['pendingFiles'], 'readwrite');
            const store = tx.objectStore('pendingFiles');
            files.forEach((file) => {
                store.add({ actionId, name: file.name, blob: file, created_at: Date.now() });
            });
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    };

    const getPendingFiles = async (actionId) => {
        const db = await openPendingUploadsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['pendingFiles'], 'readonly');
            const store = tx.objectStore('pendingFiles');
            const idx = store.index('actionId');
            const req = idx.getAll(actionId);
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    };

    const clearPendingFiles = async (actionId) => {
        const db = await openPendingUploadsDB();
        const files = await getPendingFiles(actionId);
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['pendingFiles'], 'readwrite');
            const store = tx.objectStore('pendingFiles');
            files.forEach((rec) => store.delete(rec.id));
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => reject(tx.error);
        });
    };

    // Sync functions for each action type
    const syncCreateCourse = async (data) => {
        const response = await fetch('/teacher/courses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to create course');
        
        const result = await response.json();
        
        // Update local storage with server ID
        if (result.course) {
            await offlineStorage.save('courses', result.course);
        }
        
        return result;
    };

    const syncUpdateCourse = async (data) => {
        const response = await fetch(`/teacher/courses/${data.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to update course');
        return await response.json();
    };

    const syncCreateEvent = async (data) => {
        const response = await fetch('/teacher/calendar/events', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to create event');
        return await response.json();
    };

    const syncUpdateEvent = async (data) => {
        const response = await fetch(`/teacher/calendar/events/${data.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to update event');
        return await response.json();
    };

    const syncDeleteEvent = async (data) => {
        const response = await fetch(`/teacher/calendar/events/${data.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            }
        });

        if (!response.ok) throw new Error('Failed to delete event');
        return await response.json();
    };

    const syncCreateClasswork = async (data) => {
        const formData = new FormData();

        // Helper to append nested objects/arrays as form fields for Laravel
        const appendFormData = (fd, value, key) => {
            if (value === null || value === undefined) return;
            if (Array.isArray(value)) {
                value.forEach((v, i) => appendFormData(fd, v, `${key}[${i}]`));
            } else if (typeof value === 'object' && !(value instanceof Blob) && !(value instanceof File)) {
                Object.keys(value).forEach(k => {
                    const v = value[k];
                    const newKey = key ? `${key}[${k}]` : k;
                    appendFormData(fd, v, newKey);
                });
            } else {
                fd.append(key, value);
            }
        };

        // Build payload excluding attachments and internal fields
        const payload = { ...data };
        delete payload.attachments;
        delete payload.pendingActionId;

        // Normalize booleans expected by Laravel
        if (typeof payload.has_submission === 'boolean') {
            payload.has_submission = payload.has_submission ? 1 : 0;
        }
        if (typeof payload.show_correct_answers === 'boolean') {
            payload.show_correct_answers = payload.show_correct_answers ? 1 : 0;
        }

        // Append nested fields
        Object.keys(payload).forEach((key) => {
            const val = payload[key];
            appendFormData(formData, val, key);
        });

        // If there are files saved for this pending action, append them
        if (data.pendingActionId) {
            try {
                const pendingFiles = await getPendingFiles(data.pendingActionId);
                if (pendingFiles && pendingFiles.length > 0) {
                    pendingFiles.forEach(rec => formData.append('attachments[]', rec.blob, rec.name));
                }
            } catch (e) {
                console.warn('Could not load pending files for action', data.pendingActionId, e);
            }
        }

        const courseId = data.course_id;
        const response = await fetch(`/teacher/courses/${courseId}/classwork`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData
        });

        if (!response.ok) throw new Error('Failed to create classwork');
        // The controller returns a redirect for Inertia; attempt JSON first, fallback to text
        let result;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            result = await response.json();
        } else {
            result = await response.text();
        }

        // Clean up any pending files after successful upload
        if (data.pendingActionId) {
            try { await clearPendingFiles(data.pendingActionId); } catch (e) { /* ignore */ }
        }

        return result;
    };

    const syncUpdateClasswork = async (data) => {
        const response = await fetch(`/teacher/classwork/${data.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to update classwork');
        return await response.json();
    };

    const syncCreateMaterial = async (data) => {
        return await syncCreateClasswork({ ...data, type: 'material' });
    };

    const syncSubmitClasswork = async (data) => {
        const formData = new FormData();

        // Helper to append nested objects/arrays as form fields for Laravel
        const appendFormData = (fd, value, key) => {
            if (value === null || value === undefined) return;
            if (Array.isArray(value)) {
                value.forEach((v, i) => appendFormData(fd, v, `${key}[${i}]`));
            } else if (typeof value === 'object' && !(value instanceof Blob) && !(value instanceof File)) {
                Object.keys(value).forEach(k => {
                    const v = value[k];
                    const newKey = key ? `${key}[${k}]` : k;
                    appendFormData(fd, v, newKey);
                });
            } else {
                fd.append(key, value);
            }
        };

        // Build payload excluding attachments and internal fields
        const payload = { ...data };
        delete payload.attachments;
        const pendingActionId = payload.pendingActionId;
        delete payload.pendingActionId;

        // Only include known fields to avoid backend confusion
        const allowed = ['content', 'link', 'quiz_answers'];
        allowed.forEach((key) => {
            if (payload[key] !== undefined) appendFormData(formData, payload[key], key);
        });

        // Append any pending files saved for this action
        if (pendingActionId) {
            try {
                const pendingFiles = await getPendingFiles(pendingActionId);
                if (pendingFiles && pendingFiles.length > 0) {
                    pendingFiles.forEach(rec => formData.append('attachments[]', rec.blob, rec.name));
                }
            } catch (e) {
                console.warn('Could not load pending files for student submission', pendingActionId, e);
            }
        }

        const classworkId = data.classwork_id;
        const response = await fetch(`/student/classwork/${classworkId}/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData
        });

        if (!response.ok) throw new Error('Failed to submit classwork');

        let result;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            result = await response.json();
        } else {
            result = await response.text();
        }

        // Clean up any pending files after successful upload
        if (pendingActionId) {
            try { await clearPendingFiles(pendingActionId); } catch (e) { /* ignore */ }
        }

        return result;
    };

    const syncUpdateGradebook = async (data) => {
        // Align with existing Laravel route that Gradebook.vue uses online
        const response = await fetch(`/teacher/courses/${data.course_id}/gradebook/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data.gradebook)
        });

        if (!response.ok) throw new Error('Failed to update gradebook');
        return await response.json();
    };

    const syncGradeSubmission = async (data) => {
        // Build the correct endpoint with course, classwork, and submission IDs
        const endpoint = `/teacher/courses/${data.course_id}/classwork/${data.classwork_id}/submissions/${data.submission_id}/grade`;

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to grade submission');

        // Some Laravel endpoints may redirect and return HTML; handle both JSON and text
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return await response.json();
        }
        // Treat non-JSON (HTML redirect) as success; return minimal info
        return { ok: true, status: response.status, contentType };
    };

    const syncCustomAction = async (endpoint, method, data) => {
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error(`Failed to sync ${endpoint}`);

        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return await response.json();
        }
        return { ok: true, status: response.status, contentType };
    };

    // Update pending actions count
    const updatePendingCount = async () => {
        const actions = await offlineStorage.getPendingActions();
        pendingActionsCount.value = actions.length;
    };

    // Get pending actions with human-readable labels
    const getPendingActionsList = async () => {
        const actions = await offlineStorage.getPendingActions();
        pendingActions.value = actions.map(action => ({
            ...action,
            label: getActionLabel(action.type, action.data),
            icon: getActionIcon(action.type)
        }));
        return pendingActions.value;
    };

    // Get human-readable label for action type
    const getActionLabel = (type, data) => {
        const labels = {
            create_course: `Create Course: ${data.name || 'New Course'}`,
            update_course: `Update Course: ${data.name || 'Course'}`,
            create_event: `Create Event: ${data.title || 'New Event'}`,
            update_event: `Update Event: ${data.title || 'Event'}`,
            delete_event: `Delete Event`,
            create_classwork: `Create ${data.type || 'Classwork'}: ${data.title || 'New'}`,
            update_classwork: `Update Classwork: ${data.title || 'Classwork'}`,
            create_material: `Create Material: ${data.title || 'New Material'}`,
            submit_classwork: `Submit Classwork`,
            update_gradebook: `Update Gradebook`,
            grade_submission: `Grade Submission`,
            custom: `Custom Action`
        };
        return labels[type] || type;
    };

    // Get icon for action type
    const getActionIcon = (type) => {
        const icons = {
            create_course: 'add_circle',
            update_course: 'edit',
            create_event: 'event',
            update_event: 'edit_calendar',
            delete_event: 'event_busy',
            create_classwork: 'assignment',
            update_classwork: 'edit_note',
            create_material: 'folder',
            submit_classwork: 'send',
            update_gradebook: 'grade',
            grade_submission: 'grading',
            custom: 'settings'
        };
        return icons[type] || 'cloud_upload';
    };

    // Retry a specific action
    const retryAction = async (actionId) => {
        const actions = await offlineStorage.getPendingActions();
        const action = actions.find(a => a.id === actionId);

        if (!action) {
            throw new Error('Action not found');
        }

        try {
            await processPendingAction(action);
            await offlineStorage.markActionSynced(action.id);
            await updatePendingCount();
            await getPendingActionsList();
            return { success: true };
        } catch (error) {
            console.error('Failed to retry action:', action, error);
            throw error;
        }
    };

    // Delete a specific pending action
    const deleteAction = async (actionId) => {
        await offlineStorage.markActionSynced(actionId);
        await updatePendingCount();
        await getPendingActionsList();
    };

    // Save action for later sync
    const saveOfflineAction = async (type, data, endpoint = null, method = 'POST') => {
        // Attach idempotency token if missing
        try {
            if (data && typeof data === 'object' && !data.client_token) {
                data.client_token = `${type}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
            }
        } catch {}
        const id = await offlineStorage.addPendingAction({
            type,
            data,
            endpoint,
            method,
            timestamp: Date.now()
        });
        
        await updatePendingCount();
        
        return {
            success: true,
            offline: true,
            id,
            message: 'Changes saved offline. Will sync when online.'
        };
    };

    // Check if we have cached data
    const hasCachedData = async (storeName) => {
        const data = await offlineStorage.getAll(storeName);
        return data && data.length > 0;
    };

    // Get cached data
    const getCachedData = async (storeName, key = null) => {
        if (key) {
            return await offlineStorage.get(storeName, key);
        }
        return await offlineStorage.getAll(storeName);
    };

    // Save data to cache
    const cacheData = async (storeName, data) => {
        if (Array.isArray(data)) {
            return await offlineStorage.saveMany(storeName, data);
        }
        return await offlineStorage.save(storeName, data);
    };

    // Lifecycle hooks
    onMounted(() => {
        // Register online/offline listeners only once globally
        if (!listenersInitialized) {
            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            listenersInitialized = true;
        }

        // Initial check
        updatePendingCount();

        // Only the first mounted instance should kick off an initial sync
        if (isOnline.value && !globalIsSyncing) {
            syncPendingActions();
        }
    });

    // Do not remove global listeners on unmount to prevent other views losing them
    onUnmounted(() => {
        // no-op; keep listeners alive for the lifetime of the app
    });

    // Computed properties
    const statusColor = computed(() => {
        if (!syncStatus.value) return '';
        
        switch (syncStatus.value.type) {
            case 'success': return 'text-green-600';
            case 'error': return 'text-red-600';
            case 'warning': return 'text-yellow-600';
            case 'syncing': return 'text-blue-600';
            default: return '';
        }
    });

    const statusIcon = computed(() => {
        if (isSyncing.value) return 'sync';
        if (!isOnline.value) return 'cloud_off';
        if (pendingActionsCount.value > 0) return 'cloud_upload';
        return 'cloud_done';
    });

    return {
        isOnline,
        isSyncing,
        syncStatus,
        pendingActionsCount,
        lastSyncTime,
        syncProgress,
        pendingActions,
        statusColor,
        statusIcon,
        syncPendingActions,
        saveOfflineAction,
        hasCachedData,
        getCachedData,
        cacheData,
        updatePendingCount,
        savePendingFiles,
        getPendingActionsList,
        retryAction,
        deleteAction
    };
}
