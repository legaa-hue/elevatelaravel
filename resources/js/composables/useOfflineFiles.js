import { ref } from 'vue';
import offlineStorage from '../offline-storage';

export function useOfflineFiles() {
    const downloadProgress = ref({});
    const downloadedFiles = ref(new Set());

    // Download and cache a file
    const downloadFile = async (url, courseId = null, metadata = {}) => {
        try {
            downloadProgress.value[url] = { progress: 0, status: 'downloading' };

            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`Failed to download: ${response.statusText}`);
            }

            const blob = await response.blob();
            
            // Convert blob to base64 for storage
            const base64 = await blobToBase64(blob);

            // Store in IndexedDB
            await offlineStorage.save('fileCache', {
                url,
                data: base64,
                type: blob.type,
                size: blob.size,
                course_id: courseId,
                cached_at: Date.now(),
                ...metadata
            });

            downloadProgress.value[url] = { progress: 100, status: 'completed' };
            downloadedFiles.value.add(url);

            return true;
        } catch (error) {
            console.error('File download error:', error);
            downloadProgress.value[url] = { progress: 0, status: 'error', error: error.message };
            return false;
        }
    };

    // Download multiple files
    const downloadFiles = async (urls, courseId = null) => {
        const results = await Promise.allSettled(
            urls.map(url => downloadFile(url, courseId))
        );

        return results.map((result, index) => ({
            url: urls[index],
            success: result.status === 'fulfilled' && result.value,
            error: result.status === 'rejected' ? result.reason : null
        }));
    };

    // Get cached file
    const getCachedFile = async (url) => {
        try {
            const cached = await offlineStorage.get('fileCache', url);
            
            if (!cached) return null;

            // Convert base64 back to blob
            const blob = base64ToBlob(cached.data, cached.type);
            
            return {
                blob,
                url: URL.createObjectURL(blob),
                type: cached.type,
                size: cached.size,
                cached_at: cached.cached_at
            };
        } catch (error) {
            console.error('Error getting cached file:', error);
            return null;
        }
    };

    // Check if file is cached
    const isFileCached = async (url) => {
        const cached = await offlineStorage.get('fileCache', url);
        return !!cached;
    };

    // Get or download file (smart caching)
    const getFile = async (url, courseId = null) => {
        // Try to get from cache first
        const cached = await getCachedFile(url);
        
        if (cached) {
            return cached;
        }

        // If online, download and cache
        if (navigator.onLine) {
            await downloadFile(url, courseId);
            return await getCachedFile(url);
        }

        return null;
    };

    // Auto-download classwork attachments
    const downloadClassworkAttachments = async (classwork) => {
        if (!classwork.attachments || classwork.attachments.length === 0) {
            return;
        }

        const urls = classwork.attachments.map(att => {
            if (att.path) {
                return `/storage/${att.path}`;
            }
            return att.url || att;
        }).filter(Boolean);

        return await downloadFiles(urls, classwork.course_id);
    };

    // Auto-download submission attachments
    const downloadSubmissionAttachments = async (submission) => {
        if (!submission.attachments || submission.attachments.length === 0) {
            return;
        }

        const urls = submission.attachments.map(att => {
            if (att.path) {
                return `/storage/${att.path}`;
            }
            return att.url || att;
        }).filter(Boolean);

        return await downloadFiles(urls, submission.course_id);
    };

    // Download all course files
    const downloadCourseFiles = async (courseId) => {
        try {
            // Get all classwork for course
            const classworks = await offlineStorage.getByIndex('classwork', 'course_id', courseId);
            
            const allUrls = [];
            
            for (const classwork of classworks) {
                if (classwork.attachments) {
                    const urls = classwork.attachments.map(att => {
                        if (att.path) return `/storage/${att.path}`;
                        return att.url || att;
                    }).filter(Boolean);
                    
                    allUrls.push(...urls);
                }
            }

            if (allUrls.length === 0) return { success: true, count: 0 };

            const results = await downloadFiles(allUrls, courseId);
            const successCount = results.filter(r => r.success).length;

            return {
                success: true,
                count: successCount,
                total: allUrls.length,
                results
            };
        } catch (error) {
            console.error('Error downloading course files:', error);
            return { success: false, error: error.message };
        }
    };

    // Clear old cached files (older than 30 days)
    const clearOldCache = async (maxAge = 30 * 24 * 60 * 60 * 1000) => {
        try {
            const allFiles = await offlineStorage.getAll('fileCache');
            const now = Date.now();
            
            let deletedCount = 0;
            
            for (const file of allFiles) {
                if (now - file.cached_at > maxAge) {
                    await offlineStorage.delete('fileCache', file.url);
                    deletedCount++;
                }
            }

            return { success: true, deletedCount };
        } catch (error) {
            console.error('Error clearing old cache:', error);
            return { success: false, error: error.message };
        }
    };

    // Get cache statistics
    const getCacheStats = async () => {
        try {
            const allFiles = await offlineStorage.getAll('fileCache');
            
            const totalSize = allFiles.reduce((sum, file) => sum + (file.size || 0), 0);
            const totalFiles = allFiles.length;

            return {
                totalFiles,
                totalSize,
                totalSizeMB: (totalSize / (1024 * 1024)).toFixed(2)
            };
        } catch (error) {
            console.error('Error getting cache stats:', error);
            return { totalFiles: 0, totalSize: 0, totalSizeMB: '0.00' };
        }
    };

    // Helper: Convert blob to base64
    const blobToBase64 = (blob) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    };

    // Helper: Convert base64 to blob
    const base64ToBlob = (base64, type) => {
        const byteString = atob(base64.split(',')[1]);
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        
        return new Blob([ab], { type });
    };

    return {
        downloadProgress,
        downloadedFiles,
        downloadFile,
        downloadFiles,
        getCachedFile,
        isFileCached,
        getFile,
        downloadClassworkAttachments,
        downloadSubmissionAttachments,
        downloadCourseFiles,
        clearOldCache,
        getCacheStats
    };
}
