/**
 * Composable for handling student classwork submissions with offline support
 * 
 * Usage in Vue component:
 * 
 * import { useOfflineSubmission } from '@/composables/useOfflineSubmission';
 * 
 * const { submitClasswork, isOnline, hasPending, pendingCount } = useOfflineSubmission();
 * 
 * // Submit classwork (works offline and online)
 * await submitClasswork(classworkId, submissionData, { courseId: 123 });
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import offlineSync from '../offline-sync';

export function useOfflineSubmission() {
    const isOnline = ref(navigator.onLine);
    const pendingSubmissions = ref([]);
    const syncing = ref(false);

    // Update online status
    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
    };

    // Load pending submissions count
    const loadPendingSubmissions = async () => {
        try {
            pendingSubmissions.value = await offlineSync.getPendingSubmissions();
        } catch (error) {
            console.error('Failed to load pending submissions:', error);
        }
    };

    // Computed
    const hasPending = computed(() => pendingSubmissions.value.length > 0);
    const pendingCount = computed(() => pendingSubmissions.value.length);

    /**
     * Submit classwork with automatic offline handling
     * 
     * @param {number} classworkId - The classwork ID
     * @param {Object} submissionData - Submission data (text, answer, files, etc.)
     * @param {Object} metadata - Additional metadata (courseId, studentId, etc.)
     * @returns {Promise<Object>} Result object with success status
     */
    const submitClasswork = async (classworkId, submissionData, metadata = {}) => {
        try {
            if (!isOnline.value) {
                // Save offline
                console.log('📵 Offline: Saving submission locally...');
                
                const offlineId = await offlineSync.saveSubmissionOffline(
                    classworkId,
                    submissionData,
                    metadata
                );

                await loadPendingSubmissions();

                return {
                    success: true,
                    offline: true,
                    offlineId,
                    message: 'Submission saved offline. Will sync when connection is restored.'
                };
            } else {
                // Submit online
                console.log('🌐 Online: Submitting directly...');
                
                const response = await fetch(`/student/classwork/${classworkId}/submit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(submissionData)
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                return {
                    success: true,
                    offline: false,
                    data: result,
                    message: 'Submission uploaded successfully!'
                };
            }
        } catch (error) {
            console.error('Submit classwork error:', error);
            
            // If online submission fails, save offline as fallback
            if (isOnline.value) {
                console.log('⚠️ Online submission failed, saving offline as fallback...');
                
                const offlineId = await offlineSync.saveSubmissionOffline(
                    classworkId,
                    submissionData,
                    metadata
                );

                await loadPendingSubmissions();

                return {
                    success: true,
                    offline: true,
                    offlineId,
                    fallback: true,
                    message: 'Submission saved offline due to connection error. Will retry when connection improves.'
                };
            }

            throw error;
        }
    };

    /**
     * Check if there's a pending submission for specific classwork
     * @param {number} classworkId 
     */
    const hasPendingForClasswork = async (classworkId) => {
        const submission = await offlineSync.getPendingSubmissionForClasswork(classworkId);
        return !!submission;
    };

    /**
     * Get pending submission details for specific classwork
     * @param {number} classworkId 
     */
    const getPendingSubmission = async (classworkId) => {
        return await offlineSync.getPendingSubmissionForClasswork(classworkId);
    };

    /**
     * Manually trigger sync
     */
    const syncNow = async () => {
        if (!isOnline.value) {
            console.log('Cannot sync while offline');
            return { success: false, message: 'Cannot sync while offline' };
        }

        try {
            syncing.value = true;
            await offlineSync.syncAll();
            await loadPendingSubmissions();
            
            return { success: true, message: 'Sync completed successfully' };
        } catch (error) {
            console.error('Sync failed:', error);
            return { success: false, message: 'Sync failed: ' + error.message };
        } finally {
            syncing.value = false;
        }
    };

    // Listen to online/offline events
    onMounted(() => {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Auto-sync when coming online
        window.addEventListener('online', async () => {
            console.log('🌐 Connection restored! Auto-syncing...');
            await syncNow();
        });

        // Load initial pending submissions
        loadPendingSubmissions();
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    });

    return {
        // State
        isOnline,
        hasPending,
        pendingCount,
        pendingSubmissions,
        syncing,

        // Methods
        submitClasswork,
        hasPendingForClasswork,
        getPendingSubmission,
        syncNow,
        loadPendingSubmissions
    };
}
