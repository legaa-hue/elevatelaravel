<template>
    <Transition name="slide-fade">
        <div v-if="showIndicator" class="fixed top-4 right-4 z-50 max-w-sm">
            <!-- Offline Indicator -->
            <div v-if="!isOnline" 
                 class="bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-3">
                <span class="material-icons text-gray-400 animate-pulse">cloud_off</span>
                <div class="flex-1">
                    <p class="font-semibold text-sm">You're offline</p>
                    <p class="text-xs text-gray-300">Changes will sync when online</p>
                </div>
                <span v-if="pendingActionsCount > 0" 
                      class="bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                    {{ pendingActionsCount }}
                </span>
            </div>

            <!-- Syncing Indicator -->
            <div v-else-if="isSyncing" 
                 class="bg-blue-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-3">
                <span class="material-icons animate-spin">sync</span>
                <div class="flex-1">
                    <p class="font-semibold text-sm">Syncing...</p>
                    <p class="text-xs text-blue-100">Uploading offline changes</p>
                </div>
            </div>

            <!-- Sync Status Messages -->
            <div v-else-if="syncStatus" 
                 :class="[
                     'px-4 py-3 rounded-lg shadow-lg flex items-center space-x-3',
                     syncStatus.type === 'success' ? 'bg-green-600 text-white' :
                     syncStatus.type === 'error' ? 'bg-red-600 text-white' :
                     syncStatus.type === 'warning' ? 'bg-yellow-600 text-white' :
                     'bg-blue-600 text-white'
                 ]">
                <span class="material-icons">
                    {{ syncStatus.type === 'success' ? 'check_circle' :
                       syncStatus.type === 'error' ? 'error' :
                       syncStatus.type === 'warning' ? 'warning' : 'info' }}
                </span>
                <p class="flex-1 text-sm font-medium">{{ syncStatus.message }}</p>
                <button @click="syncStatus = null" class="text-white hover:text-gray-200">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>

            <!-- Pending Actions Indicator (when online but has pending) -->
            <div v-else-if="isOnline && pendingActionsCount > 0" 
                 class="bg-yellow-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-3 cursor-pointer"
                 @click="$emit('retry-sync')">
                <span class="material-icons">cloud_upload</span>
                <div class="flex-1">
                    <p class="font-semibold text-sm">{{ pendingActionsCount }} pending changes</p>
                    <p class="text-xs text-yellow-100">Click to sync now</p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { useOfflineSync } from '../composables/useOfflineSync';

const {
    isOnline,
    isSyncing,
    syncStatus,
    pendingActionsCount
} = useOfflineSync();

const showIndicator = computed(() => {
    return !isOnline.value || isSyncing.value || syncStatus.value || pendingActionsCount.value > 0;
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
