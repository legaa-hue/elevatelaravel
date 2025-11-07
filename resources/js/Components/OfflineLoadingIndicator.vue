<template>
    <Transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm mx-4">
                <div class="flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <div>
                        <p class="font-semibold text-gray-900">Loading...</p>
                        <p class="text-sm text-gray-600">Loading offline content</p>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const show = ref(false);
const isOnline = ref(navigator.onLine);
let timeout = null;
let removeStartListener = null;
let removeFinishListener = null;

const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
};

const showIndicator = () => {
    // Update online status when showing
    updateOnlineStatus();
    
    // Only show if loading takes more than 300ms
    timeout = setTimeout(() => {
        show.value = true;
    }, 300);
};

const hideIndicator = () => {
    if (timeout) {
        clearTimeout(timeout);
        timeout = null;
    }
    show.value = false;
};

onMounted(() => {
    router.on('start', showIndicator);
    router.on('finish', hideIndicator);
});

onUnmounted(() => {
    router.off('start', showIndicator);
    router.off('finish', hideIndicator);
    if (timeout) clearTimeout(timeout);
    removeStartListener = router.on('start', showIndicator);
    removeFinishListener = router.on('finish', hideIndicator);
    
    // Listen for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
});

onUnmounted(() => {
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
    if (timeout) clearTimeout(timeout);
    
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
