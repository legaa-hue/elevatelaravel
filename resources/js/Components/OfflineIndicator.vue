<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import offlineStorage from '@/offline-storage';

const isOnline = ref(navigator.onLine);
const pendingActionsCount = ref(0);

const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    if (isOnline.value) {
        checkPendingActions();
    }
};

const checkPendingActions = async () => {
    const actions = await offlineStorage.getPendingActions();
    pendingActionsCount.value = actions.length;
};

onMounted(() => {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    checkPendingActions();
});

onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
});
</script>

<template>
    <!-- Offline Banner -->
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform -translate-y-full opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-full opacity-0"
    >
        <div v-if="!isOnline" class="fixed top-0 left-0 right-0 z-50 bg-amber-500 text-white px-4 py-3 shadow-lg">
            <div class="container mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="h-5 w-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                    </svg>
                    <div>
                        <p class="font-semibold">You're offline</p>
                        <p class="text-sm text-amber-100">Your changes will be saved and synced when you reconnect</p>
                    </div>
                </div>
                
                <div v-if="pendingActionsCount > 0" class="bg-white text-amber-600 px-3 py-1 rounded-full text-sm font-medium">
                    {{ pendingActionsCount }} pending {{ pendingActionsCount === 1 ? 'action' : 'actions' }}
                </div>
            </div>
        </div>
    </Transition>

    <!-- Online Notification (briefly show when reconnected) -->
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform -translate-y-full opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-full opacity-0"
    >
        <div v-if="isOnline && pendingActionsCount > 0" class="fixed top-0 left-0 right-0 z-50 bg-green-500 text-white px-4 py-3 shadow-lg">
            <div class="container mx-auto flex items-center space-x-3">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold">Back online!</p>
                    <p class="text-sm text-green-100">Syncing your changes...</p>
                </div>
            </div>
        </div>
    </Transition>
</template>
