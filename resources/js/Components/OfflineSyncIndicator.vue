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
                 class="bg-yellow-600 text-white px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 cursor-pointer text-xs"
                 @click="$emit('retry-sync')">
                <span class="material-icons text-sm">cloud_upload</span>
                <div class="flex-1">
                    <p class="font-semibold">{{ pendingActionsCount }} pending</p>
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
