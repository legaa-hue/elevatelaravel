// Student Offline Composable - Complete offline support for student features
import { ref, computed, onMounted, onUnmounted } from 'vue';
import offlineSync from '../offline-sync';

export function useStudentOffline() {
    const isOnline = ref(navigator.onLine);
    const syncing = ref(false);
    const pendingCount = ref(0);
    const pendingSubmissions = ref([]);
    const error = ref(null);

    // Update online status
    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
        
        if (isOnline.value) {
            console.log('🌐 Connection restored! Auto-syncing student data...');
            syncNow();
        } else {
            console.log('📴 Connection lost - entering offline mode');
        }
    };

    // Load pending submissions count
    const loadPendingSubmissions = async () => {
        try {
            pendingCount.value = await offlineSync.getPendingSubmissionsCount();
            pendingSubmissions.value = await offlineSync.getPendingSubmissions();
        } catch (err) {
            console.error('Failed to load pending submissions:', err);
        }
    };

    // Check if specific classwork has pending submission
    const hasPendingForClasswork = async (classworkId) => {
        try {
            const pending = await offlineSync.getPendingSubmissionForClasswork(classworkId);
            return pending !== null;
        } catch (err) {
            console.error('Failed to check pending submission:', err);
            return false;
        }
    };

    // Get pending submission for specific classwork
    const getPendingSubmission = async (classworkId) => {
        try {
            return await offlineSync.getPendingSubmissionForClasswork(classworkId);
        } catch (err) {
            console.error('Failed to get pending submission:', err);
            return null;
        }
    };

    // ===========================
    // SUBMISSION SYNC
    // ===========================

    /**
     * Submit classwork with automatic offline handling
     * @param {number} classworkId - The classwork ID
     * @param {Object} submissionData - The submission data (content, files, quiz_answers, link)
     * @param {Object} metadata - Additional metadata (courseId, courseName, classworkTitle, etc.)
     * @returns {Promise<Object>} Result object with success, offline flags
     */
    const submitClasswork = async (classworkId, submissionData, metadata = {}) => {
        error.value = null;

        try {
            if (isOnline.value) {
                // Try online submission first
                const formData = new FormData();
                
                // Add content
                if (submissionData.content) {
                    formData.append('content', submissionData.content);
                }
                
                // Add link
                if (submissionData.link) {
                    formData.append('link', submissionData.link);
                }
                
                // Add quiz answers
                if (submissionData.quiz_answers) {
                    formData.append('quiz_answers', JSON.stringify(submissionData.quiz_answers));
                }
                
                // Add file attachments
                if (submissionData.attachments && submissionData.attachments.length > 0) {
                    submissionData.attachments.forEach((file, index) => {
                        formData.append(`attachments[${index}]`, file);
                    });
                }

                const response = await fetch(`/student/classwork/${classworkId}/submit`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });

                if (response.ok) {
                    await loadPendingSubmissions();
                    return {
                        success: true,
                        offline: false,
                        message: 'Submission uploaded successfully!'
                    };
                } else {
                    throw new Error('Server returned an error');
                }
            } else {
                throw new Error('No internet connection');
            }
        } catch (err) {
            console.log('Online submission failed, saving offline...', err);
            
            // Save offline
            const offlineId = await offlineSync.saveSubmissionOffline(classworkId, submissionData, {
                ...metadata,
                classworkId,
                savedAt: new Date().toISOString(),
            });

            await loadPendingSubmissions();

            return {
                success: true,
                offline: true,
                offlineId,
                message: '📴 Submission saved offline. Will sync when you\'re back online!'
            };
        }
    };

    /**
     * Request grade access with offline support
     */
    const requestGradeAccess = async (courseId, metadata = {}) => {
        error.value = null;

        try {
            if (isOnline.value) {
                const response = await fetch(`/student/courses/${courseId}/request-grade-access`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    return {
                        success: true,
                        offline: false,
                        message: 'Grade access requested successfully!'
                    };
                }
            }
            throw new Error('Failed to request grade access');
        } catch (err) {
            console.log('Request failed, queuing offline...', err);
            
            // Queue for later sync
            await offlineSync.queueRequest(
                'request-grade-access',
                'POST',
                `/student/courses/${courseId}/request-grade-access`,
                {},
                {
                    ...metadata,
                    courseId,
                    requestedAt: new Date().toISOString(),
                }
            );

            return {
                success: true,
                offline: true,
                message: '📴 Grade access request saved offline. Will sync when you\'re back online!'
            };
        }
    };

    /**
     * Unsubmit classwork with offline support
     */
    const unsubmitClasswork = async (classworkId, metadata = {}) => {
        error.value = null;

        try {
            if (isOnline.value) {
                const response = await fetch(`/student/classwork/${classworkId}/unsubmit`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    return {
                        success: true,
                        offline: false,
                        message: 'Submission removed successfully!'
                    };
                }
            }
            throw new Error('Failed to unsubmit');
        } catch (err) {
            console.log('Unsubmit failed, queuing offline...', err);
            
            // Queue for later sync
            await offlineSync.queueRequest(
                'unsubmit-classwork',
                'DELETE',
                `/student/classwork/${classworkId}/unsubmit`,
                {},
                {
                    ...metadata,
                    classworkId,
                    unsubmittedAt: new Date().toISOString(),
                }
            );

            return {
                success: true,
                offline: true,
                message: '📴 Unsubmit request saved offline. Will sync when you\'re back online!'
            };
        }
    };

    // ===========================
    // SYNC MANAGEMENT
    // ===========================

    /**
     * Manually trigger sync of all pending student data
     */
    const syncNow = async () => {
        if (!isOnline.value) {
            console.log('Cannot sync - offline');
            return { success: false, message: 'Cannot sync while offline' };
        }

        if (syncing.value) {
            console.log('Sync already in progress');
            return { success: false, message: 'Sync already in progress' };
        }

        syncing.value = true;
        error.value = null;

        try {
            console.log('🔄 Starting student data sync...');
            
            const result = await offlineSync.syncAll();
            
            await loadPendingSubmissions();
            
            console.log('✅ Sync completed:', result);
            
            return {
                success: true,
                message: `Synced ${result.synced} items successfully!`,
                result
            };
        } catch (err) {
            console.error('❌ Sync failed:', err);
            error.value = err.message;
            
            return {
                success: false,
                message: 'Sync failed: ' + err.message,
                error: err
            };
        } finally {
            syncing.value = false;
        }
    };

    /**
     * Clear all offline student data (use with caution!)
     */
    const clearAllOfflineData = async () => {
        try {
            await offlineSync.clearAll();
            await loadPendingSubmissions();
            
            return {
                success: true,
                message: 'All offline data cleared successfully!'
            };
        } catch (err) {
            console.error('Failed to clear offline data:', err);
            error.value = err.message;
            
            return {
                success: false,
                message: 'Failed to clear offline data: ' + err.message
            };
        }
    };

    // ===========================
    // LIFECYCLE
    // ===========================

    onMounted(() => {
        // Add event listeners
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Load initial pending count
        loadPendingSubmissions();
        
        // Auto-sync on mount if online
        if (isOnline.value) {
            syncNow();
        }
    });

    onUnmounted(() => {
        // Remove event listeners
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    });

    // ===========================
    // COMPUTED PROPERTIES
    // ===========================

    const hasPending = computed(() => pendingCount.value > 0);
    
    const statusText = computed(() => {
        if (!isOnline.value) return '📴 Offline Mode';
        if (syncing.value) return '🔄 Syncing...';
        if (hasPending.value) return `⚠️ ${pendingCount.value} Pending`;
        return '✅ All Synced';
    });

    const statusClass = computed(() => {
        if (!isOnline.value) return 'text-orange-600 bg-orange-50';
        if (syncing.value) return 'text-blue-600 bg-blue-50';
        if (hasPending.value) return 'text-yellow-600 bg-yellow-50';
        return 'text-green-600 bg-green-50';
    });

    // ===========================
    // RETURN PUBLIC API
    // ===========================

    return {
        // State
        isOnline,
        syncing,
        hasPending,
        pendingCount,
        pendingSubmissions,
        error,
        statusText,
        statusClass,
        
        // Submission methods
        submitClasswork,
        hasPendingForClasswork,
        getPendingSubmission,
        unsubmitClasswork,
        
        // Grade access
        requestGradeAccess,
        
        // Sync methods
        syncNow,
        loadPendingSubmissions,
        clearAllOfflineData,
    };
}

/**
 * Usage Example:
 * 
 * <script setup>
 * import { useStudentOffline } from '@/composables/useStudentOffline';
 * 
 * const {
 *   isOnline,
 *   hasPending,
 *   pendingCount,
 *   statusText,
 *   statusClass,
 *   submitClasswork,
 *   syncNow
 * } = useStudentOffline();
 * 
 * // Submit classwork
 * const handleSubmit = async () => {
 *   const result = await submitClasswork(
 *     classworkId,
 *     { content: 'My answer', attachments: files },
 *     { courseId: 1, courseName: 'Math 101' }
 *   );
 *   
 *   if (result.offline) {
 *     alert('Saved offline! Will sync when online.');
 *   } else {
 *     alert('Submitted successfully!');
 *   }
 * };
 * </script>
 * 
 * <template>
 *   <!-- Status indicator -->
 *   <div :class="statusClass">{{ statusText }}</div>
 *   
 *   <!-- Pending badge -->
 *   <span v-if="hasPending">{{ pendingCount }} pending</span>
 *   
 *   <!-- Manual sync button -->
 *   <button @click="syncNow" :disabled="!isOnline">Sync Now</button>
 * </template>
 */
