<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const showInstallPrompt = ref(false);
const deferredPrompt = ref(null);
const isIOS = ref(false);
const isStandalone = ref(false);
const showIOSInstructions = ref(false);

onMounted(() => {
    // Check if already installed
    isStandalone.value = window.matchMedia('(display-mode: standalone)').matches || 
                         window.navigator.standalone === true;

    // Check if iOS
    isIOS.value = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    // Don't show if already installed
    if (isStandalone.value) {
        return;
    }

    // Check if user previously dismissed
    const dismissed = localStorage.getItem('pwa-install-dismissed');
    const dismissedTime = localStorage.getItem('pwa-install-dismissed-time');
    
    if (dismissed && dismissedTime) {
        const daysSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60 * 24);
        if (daysSinceDismissed < 7) {
            return; // Don't show for 7 days
        }
    }

    // Show iOS instructions if iOS and not installed
    if (isIOS.value) {
        setTimeout(() => {
            showInstallPrompt.value = true;
        }, 3000); // Show after 3 seconds
        return;
    }

    // Listen for beforeinstallprompt event (Chrome, Edge, etc.)
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});

const handleBeforeInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    
    // Show prompt after a delay
    setTimeout(() => {
        showInstallPrompt.value = true;
    }, 3000); // Show after 3 seconds
};

const installApp = async () => {
    if (isIOS.value) {
        showIOSInstructions.value = true;
        return;
    }

    if (!deferredPrompt.value) {
        return;
    }

    deferredPrompt.value.prompt();
    
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === 'accepted') {
        console.log('User accepted the install prompt');
    } else {
        console.log('User dismissed the install prompt');
    }
    
    deferredPrompt.value = null;
    showInstallPrompt.value = false;
};

const dismissPrompt = () => {
    showInstallPrompt.value = false;
    localStorage.setItem('pwa-install-dismissed', 'true');
    localStorage.setItem('pwa-install-dismissed-time', Date.now().toString());
};

const closeIOSInstructions = () => {
    showIOSInstructions.value = false;
    dismissPrompt();
};
</script>

<template>
    <!-- Install Prompt Banner -->
    <Transition name="slide-down">
        <div 
            v-if="showInstallPrompt && !showIOSInstructions"
            class="fixed top-0 left-0 right-0 z-[9999] bg-gradient-to-r from-red-900 to-red-700 text-white shadow-lg"
        >
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Install ElevateGS App</p>
                            <p class="text-sm text-red-100">Add to your home screen for quick access & offline features</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="dismissPrompt"
                            class="px-4 py-2 text-white hover:bg-white/10 rounded-lg transition"
                        >
                            Not now
                        </button>
                        <button
                            @click="installApp"
                            class="px-6 py-2 bg-white text-red-900 hover:bg-red-50 rounded-lg font-semibold transition flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Install
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- iOS Installation Instructions Modal -->
    <Transition name="fade">
        <div 
            v-if="showIOSInstructions"
            class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center p-4"
            @click.self="closeIOSInstructions"
        >
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-900 to-red-700 text-white p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.71,19.5C17.88,20.74 17,21.95 15.66,21.97C14.32,22 13.89,21.18 12.37,21.18C10.84,21.18 10.37,21.95 9.1,22C7.79,22.05 6.8,20.68 5.96,19.47C4.25,17 2.94,12.45 4.7,9.39C5.57,7.87 7.13,6.91 8.82,6.88C10.1,6.86 11.32,7.75 12.11,7.75C12.89,7.75 14.37,6.68 15.92,6.84C16.57,6.87 18.39,7.1 19.56,8.82C19.47,8.88 17.39,10.1 17.41,12.63C17.44,15.65 20.06,16.66 20.09,16.67C20.06,16.74 19.67,18.11 18.71,19.5M13,3.5C13.73,2.67 14.94,2.04 15.94,2C16.07,3.17 15.6,4.35 14.9,5.19C14.21,6.04 13.07,6.7 11.95,6.61C11.8,5.46 12.36,4.26 13,3.5Z" />
                            </svg>
                            Install on iPhone
                        </h3>
                        <button
                            @click="closeIOSInstructions"
                            class="text-white hover:bg-white/20 rounded-lg p-1 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="p-6 space-y-4">
                    <p class="text-gray-700 mb-4">
                        To install ElevateGS on your iPhone or iPad:
                    </p>

                    <div class="space-y-4">
                        <!-- Step 1 -->
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-red-100 text-red-900 rounded-full flex items-center justify-center font-bold">
                                1
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">Tap the Share button</p>
                                <div class="flex items-center gap-2 mt-1 text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16,5L19,8H17A6,6 0 0,0 11,14V16.5L6,12L11,7.5V10A8,8 0 0,1 19,18H21A10,10 0 0,0 11,8V5L16,5Z" />
                                    </svg>
                                    <span>at the bottom of Safari</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-red-100 text-red-900 rounded-full flex items-center justify-center font-bold">
                                2
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">Select "Add to Home Screen"</p>
                                <div class="flex items-center gap-2 mt-1 text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Scroll down if needed</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-red-100 text-red-900 rounded-full flex items-center justify-center font-bold">
                                3
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-900 font-medium">Tap "Add"</p>
                                <p class="text-sm text-gray-600 mt-1">The app will appear on your home screen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Hint -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-blue-900">
                                This will let you access ElevateGS like a native app with offline support!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button
                        @click="closeIOSInstructions"
                        class="px-6 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition font-medium"
                    >
                        Got it!
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

.slide-down-enter-from {
    transform: translateY(-100%);
    opacity: 0;
}

.slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
