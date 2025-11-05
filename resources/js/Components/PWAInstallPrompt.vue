<template>
    <!-- Install PWA Button -->
    <div v-if="canInstall && !isInstalled" class="fixed bottom-4 right-4 z-50">
        <button
            @click="handleInstall"
            class="flex items-center gap-2 px-4 py-3 bg-red-900 hover:bg-red-800 text-white rounded-lg shadow-lg transition-all duration-200 hover:scale-105"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span class="font-medium">Install App</span>
        </button>
    </div>

    <!-- Already Installed Indicator -->
    <div v-if="isInstalled" class="fixed bottom-4 right-4 z-50">
        <div class="flex items-center gap-2 px-4 py-3 bg-green-600 text-white rounded-lg shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="font-medium">App Installed</span>
        </div>
    </div>
</template>

<script setup>
import { usePWAInstall } from '@/composables/usePWAInstall';

const { canInstall, isInstalled, showInstallPrompt } = usePWAInstall();

const handleInstall = async () => {
    const installed = await showInstallPrompt();
    if (installed) {
        console.log('Successfully installed!');
    }
};
</script>
