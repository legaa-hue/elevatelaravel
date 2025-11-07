import { ref, computed, onMounted } from 'vue';

export function useStorageQuota() {
    const quota = ref(0);
    const usage = ref(0);
    const usagePercentage = computed(() => {
        if (quota.value === 0) return 0;
        return Math.round((usage.value / quota.value) * 100);
    });

    const isNearLimit = computed(() => usagePercentage.value >= 80);
    const isAtLimit = computed(() => usagePercentage.value >= 95);

    const formatBytes = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const usageFormatted = computed(() => formatBytes(usage.value));
    const quotaFormatted = computed(() => formatBytes(quota.value));

    const checkQuota = async () => {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            try {
                const estimate = await navigator.storage.estimate();
                quota.value = estimate.quota || 0;
                usage.value = estimate.usage || 0;

                console.log(`📊 Storage: ${usageFormatted.value} / ${quotaFormatted.value} (${usagePercentage.value}%)`);

                if (isAtLimit.value) {
                    console.warn('⚠️ Storage almost full! Please clear cache.');
                } else if (isNearLimit.value) {
                    console.warn('⚠️ Storage usage is high (>80%)');
                }

                return {
                    quota: quota.value,
                    usage: usage.value,
                    percentage: usagePercentage.value
                };
            } catch (error) {
                console.error('Failed to check storage quota:', error);
            }
        } else {
            console.warn('Storage API not supported');
        }
        return null;
    };

    const clearOldCache = async (maxAge = 7 * 24 * 60 * 60 * 1000) => {
        // Clear cache items older than maxAge (default 7 days)
        try {
            const db = await openDB();
            const stores = ['fileCache', 'visitedPages', 'reports', 'dashboardCache'];
            const now = Date.now();

            for (const storeName of stores) {
                if (!db.objectStoreNames.contains(storeName)) continue;

                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                const all = await store.getAll();

                for (const item of all) {
                    if (item.cached_at && (now - item.cached_at) > maxAge) {
                        await store.delete(item.id || item.key);
                        console.log(`🗑️ Cleared old cache: ${storeName}/${item.id || item.key}`);
                    }
                }
            }

            console.log('✅ Old cache cleared');
            await checkQuota();
        } catch (error) {
            console.error('Failed to clear old cache:', error);
        }
    };

    const clearAllCache = async () => {
        try {
            const db = await openDB();
            const stores = ['fileCache', 'visitedPages', 'reports', 'dashboardCache'];

            for (const storeName of stores) {
                if (!db.objectStoreNames.contains(storeName)) continue;

                const tx = db.transaction(storeName, 'readwrite');
                await tx.objectStore(storeName).clear();
                console.log(`🗑️ Cleared store: ${storeName}`);
            }

            // Also clear Cache API
            if ('caches' in window) {
                const cacheNames = await caches.keys();
                for (const cacheName of cacheNames) {
                    if (cacheName.includes('cache') && !cacheName.includes('precache')) {
                        await caches.delete(cacheName);
                        console.log(`🗑️ Cleared cache: ${cacheName}`);
                    }
                }
            }

            console.log('✅ All cache cleared');
            await checkQuota();
        } catch (error) {
            console.error('Failed to clear cache:', error);
        }
    };

    const openDB = async () => {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('ElevateGS_Offline', 5);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    };

    // LRU Cache Eviction for fileCache
    const evictLRUFiles = async (targetCount = 50) => {
        try {
            const db = await openDB();
            if (!db.objectStoreNames.contains('fileCache')) return;

            const tx = db.transaction('fileCache', 'readwrite');
            const store = tx.objectStore('fileCache');
            const index = store.index('cached_at');
            const all = await index.getAll();

            // Sort by cached_at ascending (oldest first)
            const sorted = all.sort((a, b) => a.cached_at - b.cached_at);

            // Remove oldest items beyond targetCount
            const toRemove = sorted.slice(0, Math.max(0, sorted.length - targetCount));

            for (const item of toRemove) {
                await store.delete(item.id);
                console.log(`🗑️ Evicted LRU file: ${item.id}`);
            }

            console.log(`✅ Evicted ${toRemove.length} old files, kept ${targetCount}`);
            await checkQuota();
        } catch (error) {
            console.error('Failed to evict LRU files:', error);
        }
    };

    // Monitor quota periodically
    const startMonitoring = (intervalMs = 60000) => {
        checkQuota();
        return setInterval(checkQuota, intervalMs);
    };

    const stopMonitoring = (intervalId) => {
        if (intervalId) {
            clearInterval(intervalId);
        }
    };

    return {
        quota,
        usage,
        usagePercentage,
        usageFormatted,
        quotaFormatted,
        isNearLimit,
        isAtLimit,
        checkQuota,
        clearOldCache,
        clearAllCache,
        evictLRUFiles,
        startMonitoring,
        stopMonitoring,
        formatBytes
    };
}
