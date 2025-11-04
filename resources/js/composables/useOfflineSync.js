import { ref, onMounted, onUnmounted, computed } from 'vue';
import offlineStorage from '../offline-storage';
import { router } from '@inertiajs/vue3';

export function useOfflineSync() {
    const isOnline = ref(navigator.onLine);
    const isSyncing = ref(false);
    const syncStatus = ref(null);
    const pendingActionsCount = ref(0);
    const lastSyncTime = ref(null);

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
        if (!isOnline.value || isSyncing.value) return;

        try {
            isSyncing.value = true;
            syncStatus.value = { type: 'syncing', message: 'Syncing data...' };

            const pendingActions = await offlineStorage.getPendingActions();
            
            if (pendingActions.length === 0) {
                isSyncing.value = false;
                return;
            }

            console.log(`🔄 Syncing ${pendingActions.length} pending actions...`);

            let successCount = 0;
            let failCount = 0;

            for (const action of pendingActions) {
                try {
                    await processPendingAction(action);
                    await offlineStorage.markActionSynced(action.id);
                    successCount++;
                } catch (error) {
                    console.error('Failed to sync action:', action, error);
                    failCount++;
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
                return await syncCreateClasswork(data);
            
            case 'update_classwork':
                return await syncUpdateClasswork(data);
            
            case 'create_material':
                return await syncCreateMaterial(data);
            
            case 'update_gradebook':
                return await syncUpdateGradebook(data);
            
            case 'grade_submission':
                return await syncGradeSubmission(data);
            
            case 'custom':
                return await syncCustomAction(endpoint, method, data);
            
            default:
                throw new Error(`Unknown action type: ${type}`);
        }
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
        
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                if (key === 'attachments' && Array.isArray(data[key])) {
                    data[key].forEach(file => formData.append('attachments[]', file));
                } else if (typeof data[key] === 'object') {
                    formData.append(key, JSON.stringify(data[key]));
                } else {
                    formData.append(key, data[key]);
                }
            }
        });

        const response = await fetch('/teacher/classwork', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData
        });

        if (!response.ok) throw new Error('Failed to create classwork');
        return await response.json();
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

    const syncUpdateGradebook = async (data) => {
        const response = await fetch(`/teacher/courses/${data.course_id}/gradebook`, {
            method: 'PUT',
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
        const response = await fetch(`/teacher/submissions/${data.submission_id}/grade`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error('Failed to grade submission');
        return await response.json();
    };

    const syncCustomAction = async (endpoint, method, data) => {
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) throw new Error(`Failed to sync ${endpoint}`);
        return await response.json();
    };

    // Update pending actions count
    const updatePendingCount = async () => {
        const actions = await offlineStorage.getPendingActions();
        pendingActionsCount.value = actions.length;
    };

    // Save action for later sync
    const saveOfflineAction = async (type, data, endpoint = null, method = 'POST') => {
        await offlineStorage.addPendingAction({
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
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Initial check
        updatePendingCount();
        
        // Try to sync if online
        if (isOnline.value) {
            syncPendingActions();
        }
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
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
        statusColor,
        statusIcon,
        syncPendingActions,
        saveOfflineAction,
        hasCachedData,
        getCachedData,
        cacheData,
        updatePendingCount
    };
}
