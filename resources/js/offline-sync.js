// Offline Sync System for Teacher Materials and Gradebook Changes
import { openDB } from 'idb';

class OfflineSync {
    constructor() {
        this.dbName = 'elevategs-offline-sync';
        this.dbVersion = 2; // Increment version to add new stores
        this.db = null;
        this.syncInProgress = false;
    }

    async init() {
        this.db = await openDB(this.dbName, this.dbVersion, {
            upgrade(db, oldVersion) {
                // Store for pending API requests
                if (!db.objectStoreNames.contains('pending-requests')) {
                    const requestStore = db.createObjectStore('pending-requests', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    requestStore.createIndex('timestamp', 'timestamp');
                    requestStore.createIndex('type', 'type');
                }

                // Store for offline materials
                if (!db.objectStoreNames.contains('offline-materials')) {
                    db.createObjectStore('offline-materials', {
                        keyPath: 'tempId'
                    });
                }

                // Store for offline gradebook changes
                if (!db.objectStoreNames.contains('offline-grades')) {
                    db.createObjectStore('offline-grades', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                }

                // ✅ NEW: Store for student submissions made offline
                if (!db.objectStoreNames.contains('offline-submissions')) {
                    const submissionStore = db.createObjectStore('offline-submissions', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    submissionStore.createIndex('classworkId', 'classworkId');
                    submissionStore.createIndex('timestamp', 'timestamp');
                    submissionStore.createIndex('synced', 'synced');
                }
            }
        });

        // Register background sync if available
        if ('serviceWorker' in navigator) {
            this.registerBackgroundSync();
        }

        // Listen for online event
        window.addEventListener('online', () => this.syncAll());
        
        console.log('Offline Sync initialized');
    }

    async registerBackgroundSync() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Check if sync is supported
            if ('sync' in registration) {
                await registration.sync.register('sync-offline-data');
                console.log('Background sync registered');
            } else {
                console.log('Background sync not supported, will use online event only');
            }
        } catch (error) {
            console.error('Background sync registration failed:', error);
        }
    }

    // Queue a request for later sync
    async queueRequest(type, method, url, data = null, metadata = {}) {
        if (!this.db) await this.init();

        const request = {
            type,
            method,
            url,
            data,
            metadata,
            timestamp: Date.now(),
            synced: false
        };

        const id = await this.db.add('pending-requests', request);
        console.log(`Queued ${type} request #${id} for sync`);
        
        return id;
    }

    // Save material created offline
    async saveMaterialOffline(courseId, materialData) {
        if (!this.db) await this.init();

        const tempId = `temp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const material = {
            tempId,
            courseId,
            ...materialData,
            createdOffline: true,
            timestamp: Date.now()
        };

        await this.db.put('offline-materials', material);
        
        // Queue the API request
        await this.queueRequest(
            'create-material',
            'POST',
            `/api/teacher/courses/${courseId}/materials`,
            materialData,
            { tempId, courseId }
        );

        return material;
    }

    // Save gradebook change offline
    async saveGradeOffline(courseId, studentId, gradeType, value) {
        if (!this.db) await this.init();

        const gradeChange = {
            courseId,
            studentId,
            gradeType,
            value,
            timestamp: Date.now(),
            synced: false
        };

        const id = await this.db.add('offline-grades', gradeChange);
        
        // Queue the API request
        await this.queueRequest(
            'update-grade',
            'POST',
            `/api/teacher/courses/${courseId}/grades/${studentId}`,
            { grade_type: gradeType, value },
            { id, courseId, studentId, gradeType }
        );

        return id;
    }

    // Get pending sync count
    async getPendingCount() {
        if (!this.db) await this.init();

        // Use all requests; no 'synced' index exists in schema
        const requests = await this.db.getAll('pending-requests');
        return requests.filter(r => !r.synced).length;
    }

    // Get all pending requests
    async getPendingRequests() {
        if (!this.db) await this.init();
        
        const allRequests = await this.db.getAll('pending-requests');
        return allRequests.filter(r => !r.synced).sort((a, b) => a.timestamp - b.timestamp);
    }

    // Sync all pending requests
    async syncAll() {
        if (this.syncInProgress) {
            console.log('Sync already in progress');
            return;
        }

        if (!navigator.onLine) {
            console.log('Cannot sync - offline');
            return;
        }

        this.syncInProgress = true;
        console.log('Starting offline sync...');

        try {
            const pendingRequests = await this.getPendingRequests();
            console.log(`Found ${pendingRequests.length} pending requests`);

            let successCount = 0;
            let failCount = 0;

            for (const request of pendingRequests) {
                try {
                    await this.syncRequest(request);
                    successCount++;
                } catch (error) {
                    console.error(`Failed to sync request #${request.id}:`, error);
                    failCount++;
                }
            }

            console.log(`Sync complete: ${successCount} successful, ${failCount} failed`);

            // Notify user if there were synced items
            if (successCount > 0) {
                this.notifyUser(`Synced ${successCount} offline change(s)`);
            }

            // Clean up old synced requests
            await this.cleanupSynced();

        } catch (error) {
            console.error('Sync error:', error);
        } finally {
            this.syncInProgress = false;
        }
    }

    // Sync a single request
    async syncRequest(request) {
        const { id, type, method, url, data, metadata } = request;

        console.log(`Syncing ${type} request #${id}...`);

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin',
            body: data ? JSON.stringify(data) : null
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        // Mark as synced
        request.synced = true;
        request.syncedAt = Date.now();
        await this.db.put('pending-requests', request);

        // Handle specific sync types
        if (type === 'create-material' && metadata.tempId) {
            await this.db.delete('offline-materials', metadata.tempId);
        } else if (type === 'update-grade' && metadata.id) {
            const grade = await this.db.get('offline-grades', metadata.id);
            if (grade) {
                grade.synced = true;
                await this.db.put('offline-grades', grade);
            }
        } else if (type === 'submit-classwork' && metadata.offlineSubmissionId) {
            // Mark submission as synced and clean up
            await this.markSubmissionSynced(metadata.offlineSubmissionId);
            // Delete after 24 hours
            setTimeout(() => this.deleteSubmission(metadata.offlineSubmissionId), 24 * 60 * 60 * 1000);
        }

        return result;
    }

    // Clean up old synced requests
    async cleanupSynced() {
        if (!this.db) await this.init();

        const allRequests = await this.db.getAll('pending-requests');
        const cutoffTime = Date.now() - (24 * 60 * 60 * 1000); // 24 hours

        for (const request of allRequests) {
            if (request.synced && request.syncedAt < cutoffTime) {
                await this.db.delete('pending-requests', request.id);
            }
        }
    }

    // Get offline materials for a course
    async getOfflineMaterials(courseId) {
        if (!this.db) await this.init();

        const allMaterials = await this.db.getAll('offline-materials');
        return allMaterials.filter(m => m.courseId === courseId);
    }

    // Get offline grade changes for a course
    async getOfflineGrades(courseId) {
        if (!this.db) await this.init();

        const allGrades = await this.db.getAll('offline-grades');
        return allGrades.filter(g => g.courseId === courseId && !g.synced);
    }

    // Clear all offline data (use with caution)
    async clearAll() {
        if (!this.db) await this.init();

        await this.db.clear('pending-requests');
        await this.db.clear('offline-materials');
        await this.db.clear('offline-grades');
        await this.db.clear('offline-submissions');
        
        // Also clear cached student data from localStorage
        await this.clearCachedStudentData();
        
        console.log('All offline data cleared (IndexedDB + localStorage cache)');
    }

    // ====================================
    // STUDENT SUBMISSION OFFLINE METHODS
    // ====================================

    /**
     * Save a student submission while offline
     * @param {number} classworkId - The classwork ID
     * @param {FormData|Object} submissionData - The submission data (text, files, etc.)
     * @param {Object} metadata - Additional metadata (student ID, course ID, etc.)
     */
    async saveSubmissionOffline(classworkId, submissionData, metadata = {}) {
        if (!this.db) await this.init();

        const submission = {
            classworkId,
            data: submissionData,
            metadata: {
                ...metadata,
                studentId: metadata.studentId || null,
                courseId: metadata.courseId || null,
                submissionType: metadata.submissionType || 'text'
            },
            timestamp: Date.now(),
            synced: false,
            syncAttempts: 0
        };

        const id = await this.db.add('offline-submissions', submission);
        console.log(`✅ Submission for classwork #${classworkId} saved offline with ID #${id}`);

        // Also queue it as a pending request
        await this.queueRequest(
            'submit-classwork',
            'POST',
            `/student/classwork/${classworkId}/submit`,
            submissionData,
            { classworkId, offlineSubmissionId: id }
        );

        return id;
    }

    /**
     * Get all pending (unsynced) submissions
     */
    async getPendingSubmissions() {
        if (!this.db) await this.init();

        const allSubmissions = await this.db.getAll('offline-submissions');
        return allSubmissions.filter(s => !s.synced);
    }

    /**
     * Get pending submissions for a specific classwork
     */
    async getPendingSubmissionForClasswork(classworkId) {
        if (!this.db) await this.init();

        const index = this.db.transaction('offline-submissions').store.index('classworkId');
        const submissions = await index.getAll(classworkId);
        return submissions.find(s => !s.synced);
    }

    /**
     * Check if there are any pending submissions
     */
    async hasPendingSubmissions() {
        const pending = await this.getPendingSubmissions();
        return pending.length > 0;
    }

    /**
     * Get count of pending submissions
     */
    async getPendingSubmissionsCount() {
        const pending = await this.getPendingSubmissions();
        return pending.length;
    }

    /**
     * Mark a submission as synced
     */
    async markSubmissionSynced(submissionId) {
        if (!this.db) await this.init();

        const submission = await this.db.get('offline-submissions', submissionId);
        if (submission) {
            submission.synced = true;
            submission.syncedAt = Date.now();
            await this.db.put('offline-submissions', submission);
            console.log(`✅ Submission #${submissionId} marked as synced`);
        }
    }

    /**
     * Delete a synced submission after successful upload
     */
    async deleteSubmission(submissionId) {
        if (!this.db) await this.init();
        await this.db.delete('offline-submissions', submissionId);
        console.log(`🗑️ Deleted submission #${submissionId}`);
    }

    // ===========================
    // STUDENT DATA CACHING
    // ===========================

    /**
     * Cache course data for offline viewing
     * @param {number} courseId - Course ID
     * @param {Object} courseData - Full course data with classworks, announcements, etc.
     */
    async cacheCourseData(courseId, courseData) {
        const cacheKey = `course_${courseId}`;
        const cacheData = {
            id: cacheKey,
            courseId,
            data: courseData,
            cachedAt: Date.now(),
            expiresAt: Date.now() + (7 * 24 * 60 * 60 * 1000) // 7 days
        };

        try {
            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            console.log(`📦 Cached course ${courseId} data for offline use`);
        } catch (err) {
            console.error('Failed to cache course data:', err);
        }
    }

    /**
     * Get cached course data
     * @param {number} courseId - Course ID
     * @returns {Object|null} Cached course data or null if expired/not found
     */
    async getCachedCourseData(courseId) {
        const cacheKey = `course_${courseId}`;
        
        try {
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;

            const cacheData = JSON.parse(cached);
            
            // Check if expired
            if (cacheData.expiresAt < Date.now()) {
                localStorage.removeItem(cacheKey);
                console.log(`🗑️ Expired cache for course ${courseId} removed`);
                return null;
            }

            console.log(`✅ Retrieved cached course ${courseId} data`);
            return cacheData.data;
        } catch (err) {
            console.error('Failed to get cached course data:', err);
            return null;
        }
    }

    /**
     * Cache student progress data
     */
    async cacheProgressData(progressData) {
        const cacheKey = 'student_progress';
        const cacheData = {
            id: cacheKey,
            data: progressData,
            cachedAt: Date.now(),
            expiresAt: Date.now() + (24 * 60 * 60 * 1000) // 24 hours
        };

        try {
            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            console.log('📦 Cached student progress data');
        } catch (err) {
            console.error('Failed to cache progress data:', err);
        }
    }

    /**
     * Get cached progress data
     */
    async getCachedProgressData() {
        const cacheKey = 'student_progress';
        
        try {
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;

            const cacheData = JSON.parse(cached);
            
            if (cacheData.expiresAt < Date.now()) {
                localStorage.removeItem(cacheKey);
                return null;
            }

            return cacheData.data;
        } catch (err) {
            console.error('Failed to get cached progress data:', err);
            return null;
        }
    }

    /**
     * Cache student dashboard data
     */
    async cacheDashboardData(dashboardData) {
        const cacheKey = 'student_dashboard';
        const cacheData = {
            id: cacheKey,
            data: dashboardData,
            cachedAt: Date.now(),
            expiresAt: Date.now() + (60 * 60 * 1000) // 1 hour
        };

        try {
            localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            console.log('📦 Cached student dashboard data');
        } catch (err) {
            console.error('Failed to cache dashboard data:', err);
        }
    }

    /**
     * Get cached dashboard data
     */
    async getCachedDashboardData() {
        const cacheKey = 'student_dashboard';
        
        try {
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;

            const cacheData = JSON.parse(cached);
            
            if (cacheData.expiresAt < Date.now()) {
                localStorage.removeItem(cacheKey);
                return null;
            }

            return cacheData.data;
        } catch (err) {
            console.error('Failed to get cached dashboard data:', err);
            return null;
        }
    }

    /**
     * Clear all cached student data
     */
    async clearCachedStudentData() {
        const keysToRemove = [];
        
        // Find all student-related cache keys
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && (key.startsWith('course_') || 
                       key === 'student_progress' || 
                       key === 'student_dashboard')) {
                keysToRemove.push(key);
            }
        }

        // Remove them
        keysToRemove.forEach(key => localStorage.removeItem(key));
        
        console.log(`🗑️ Cleared ${keysToRemove.length} cached student data items`);
    }

    // ===========================
    // NOTIFICATIONS
    // ===========================

    // Notify user
    notifyUser(message) {
        // Try to show a notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('ElevateGS Sync', {
                body: message,
                icon: '/favicon.ico',
                tag: 'offline-sync'
            });
        } else {
            // Fallback to console
            console.log('Sync notification:', message);
        }
    }
}

// Create singleton instance
const offlineSync = new OfflineSync();

export default offlineSync;
