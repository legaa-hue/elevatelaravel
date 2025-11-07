<template>
    <Transition name="slide-fade">
        <div v-if="showIndicator" class="fixed bottom-4 right-4 z-50 max-w-xs">
            <!-- Offline Indicator -->
            <div v-if="!isOnline" 
                 class="bg-gray-800 text-white px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 text-xs">
                <span class="material-icons text-gray-400 animate-pulse text-sm">cloud_off</span>
                <div class="flex-1">
                    <p class="font-semibold">Offline</p>
                </div>
                <span v-if="pendingActionsCount > 0" 
                      class="bg-yellow-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                    {{ pendingActionsCount }}
                </span>
            </div>

            <!-- Syncing Indicator - More subtle -->
            <div v-else-if="isSyncing" 
                 class="bg-blue-500 bg-opacity-90 text-white px-3 py-2 rounded-lg shadow-md flex items-center space-x-2 text-xs">
                <span class="material-icons animate-spin text-sm">sync</span>
                <p class="font-medium">Syncing...</p>
            </div>

            <!-- Sync Status Messages - Auto-hide success, show errors -->
            <div v-else-if="syncStatus && syncStatus.type !== 'success'" 
                 :class="[
                     'px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 text-xs',
                     syncStatus.type === 'error' ? 'bg-red-600 text-white' :
                     syncStatus.type === 'warning' ? 'bg-yellow-600 text-white' :
                     'bg-blue-600 text-white'
                 ]">
                <span class="material-icons text-sm">
                    {{ syncStatus.type === 'error' ? 'error' :
                       syncStatus.type === 'warning' ? 'warning' : 'info' }}
                </span>
                <p class="flex-1 font-medium">{{ syncStatus.message }}</p>
                <button @click="syncStatus = null" class="text-white hover:text-gray-200">
                    <span class="material-icons text-xs">close</span>
                </button>
            </div>

            <!-- Pending Actions Indicator (when online but has pending) -->
            <div v-else-if="isOnline && pendingActionsCount > 0"
                 class="bg-yellow-600 text-white px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 text-xs">
                <span class="material-icons text-sm">cloud_upload</span>
                <div class="flex-1">
                    <p class="font-semibold">{{ pendingActionsCount }} pending</p>
                </div>
                <button @click="showSyncManager = true" class="text-white hover:text-gray-200 text-xs px-2 py-1 bg-black bg-opacity-20 rounded">
                    Manage
                </button>
            </div>

            <!-- Storage Warning (when near limit) -->
            <div v-else-if="isNearLimit"
                 :class="[
                     'px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 text-xs mt-2',
                     isAtLimit ? 'bg-red-600 text-white' : 'bg-orange-500 text-white'
                 ]">
                <span class="material-icons text-sm">storage</span>
                <div class="flex-1">
                    <p class="font-semibold">Storage {{ isAtLimit ? 'Full' : 'High' }}</p>
                    <p class="text-[10px] opacity-90">{{ usageFormatted }} / {{ quotaFormatted }} ({{ usagePercentage }}%)</p>
                </div>
                <button @click="clearCache" class="text-white hover:text-gray-200 text-[10px] px-2 py-1 bg-black bg-opacity-20 rounded">
                    Clear
                </button>
            </div>
        </div>
    </Transition>

    <!-- Sync Manager Modal -->
    <OfflineSyncManager
        :show="showSyncManager"
        @close="showSyncManager = false"
        @sync-all="handleSyncAll" />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useOfflineSync } from '../composables/useOfflineSync';
import { useStorageQuota } from '../composables/useStorageQuota';
import OfflineSyncManager from './OfflineSyncManager.vue';

const {
    isOnline,
    isSyncing,
    syncStatus,
    pendingActionsCount,
    syncPendingActions
} = useOfflineSync();

const {
    usagePercentage,
    usageFormatted,
    quotaFormatted,
    isNearLimit,
    isAtLimit,
    clearOldCache,
    startMonitoring,
    stopMonitoring
} = useStorageQuota();

let quotaMonitorInterval = null;
const showSyncManager = ref(false);

const showIndicator = computed(() => {
    return !isOnline.value ||
           isSyncing.value ||
           syncStatus.value ||
           pendingActionsCount.value > 0 ||
           isNearLimit.value;
});

const clearCache = async () => {
    try {
        await clearOldCache();
        console.log('✅ Cache cleared successfully');
    } catch (error) {
        console.error('Failed to clear cache:', error);
    }
};

const handleSyncAll = async () => {
    try {
        await syncPendingActions();
    } catch (error) {
        console.error('Failed to sync all actions:', error);
    }
};

onMounted(() => {
    // Start monitoring storage quota every minute
    quotaMonitorInterval = startMonitoring(60000);
});

onUnmounted(() => {
    stopMonitoring(quotaMonitorInterval);
});

defineEmits(['retry-sync']);
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}
</style>
