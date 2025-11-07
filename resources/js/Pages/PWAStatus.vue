<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const checks = ref({
    https: false,
    manifest: false,
    serviceWorker: false,
    icons: false,
    canInstall: false,
    isInstalled: false,
    manifestData: null,
    swRegistration: null,
});

const checkPWARequirements = async () => {
    // 1. Check HTTPS (or localhost)
    checks.value.https = window.location.protocol === 'https:' || 
                         window.location.hostname === 'localhost' ||
                         window.location.hostname === '127.0.0.1';

    // 2. Check Manifest
    try {
        const manifestLink = document.querySelector('link[rel="manifest"]');
        if (manifestLink) {
            const response = await fetch(manifestLink.href);
            checks.value.manifestData = await response.json();
            checks.value.manifest = true;
            
            // Check icons
            checks.value.icons = checks.value.manifestData.icons && 
                                checks.value.manifestData.icons.length > 0;
        }
    } catch (error) {
        console.error('Manifest check failed:', error);
    }

    // 3. Check Service Worker
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            checks.value.swRegistration = registration;
            checks.value.serviceWorker = !!registration;
        } catch (error) {
            console.error('Service Worker check failed:', error);
        }
    }

    // 4. Check if already installed
    checks.value.isInstalled = window.matchMedia('(display-mode: standalone)').matches;

    // 5. Listen for install prompt
    window.addEventListener('beforeinstallprompt', () => {
        checks.value.canInstall = true;
    });
};

onMounted(() => {
    checkPWARequirements();
});
</script>

<template>
    <Head title="PWA Status" />
    
    <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">PWA Installation Status</h1>
                
                <!-- HTTPS Check -->
                <div class="mb-6 flex items-center">
                    <div class="flex-shrink-0">
                        <span v-if="checks.https" class="text-green-500 text-2xl">✅</span>
                        <span v-else class="text-red-500 text-2xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium">HTTPS / Localhost</h3>
                        <p class="text-gray-600">{{ checks.https ? 'Running on secure connection' : 'Must use HTTPS or localhost' }}</p>
                        <p class="text-sm text-gray-500">Current: {{ window.location.protocol }}//{{ window.location.hostname }}</p>
                    </div>
                </div>

                <!-- Manifest Check -->
                <div class="mb-6 flex items-center">
                    <div class="flex-shrink-0">
                        <span v-if="checks.manifest" class="text-green-500 text-2xl">✅</span>
                        <span v-else class="text-red-500 text-2xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium">Web App Manifest</h3>
                        <p class="text-gray-600">{{ checks.manifest ? 'Manifest found and loaded' : 'Manifest not found' }}</p>
                        <div v-if="checks.manifestData" class="mt-2 text-sm text-gray-500">
                            <p>Name: {{ checks.manifestData.name }}</p>
                            <p>Short Name: {{ checks.manifestData.short_name }}</p>
                            <p>Start URL: {{ checks.manifestData.start_url }}</p>
                        </div>
                    </div>
                </div>

                <!-- Icons Check -->
                <div class="mb-6 flex items-center">
                    <div class="flex-shrink-0">
                        <span v-if="checks.icons" class="text-green-500 text-2xl">✅</span>
                        <span v-else class="text-red-500 text-2xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium">App Icons</h3>
                        <p class="text-gray-600">{{ checks.icons ? `${checks.manifestData?.icons?.length || 0} icons configured` : 'No icons found' }}</p>
                        <div v-if="checks.icons" class="mt-2 text-sm text-gray-500">
                            <p v-for="(icon, index) in checks.manifestData.icons" :key="index">
                                {{ icon.sizes }} - {{ icon.type }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Service Worker Check -->
                <div class="mb-6 flex items-center">
                    <div class="flex-shrink-0">
                        <span v-if="checks.serviceWorker" class="text-green-500 text-2xl">✅</span>
                        <span v-else class="text-red-500 text-2xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium">Service Worker</h3>
                        <p class="text-gray-600">{{ checks.serviceWorker ? 'Service Worker registered' : 'Service Worker not registered' }}</p>
                        <p v-if="checks.swRegistration" class="text-sm text-gray-500">
                            Scope: {{ checks.swRegistration.scope }}
                        </p>
                    </div>
                </div>

                <!-- Installation Status -->
                <div class="mb-6 flex items-center">
                    <div class="flex-shrink-0">
                        <span v-if="checks.isInstalled" class="text-green-500 text-2xl">✅</span>
                        <span v-else-if="checks.canInstall" class="text-yellow-500 text-2xl">⚠️</span>
                        <span v-else class="text-gray-500 text-2xl">⏳</span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium">Installation Status</h3>
                        <p class="text-gray-600">
                            <span v-if="checks.isInstalled">App is currently installed</span>
                            <span v-else-if="checks.canInstall">App can be installed</span>
                            <span v-else>Waiting for install prompt...</span>
                        </p>
                    </div>
                </div>

                <!-- Browser Info -->
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">Browser Information</h3>
                    <p class="text-sm text-gray-600">User Agent: {{ navigator.userAgent }}</p>
                    <p class="text-sm text-gray-600 mt-1">Display Mode: {{ window.matchMedia('(display-mode: standalone)').matches ? 'Standalone' : 'Browser' }}</p>
                </div>

                <!-- Troubleshooting -->
                <div class="mt-8 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                    <h3 class="font-medium text-blue-900 mb-2">💡 Troubleshooting Tips</h3>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li v-if="!checks.https">Use HTTPS or localhost to enable PWA features</li>
                        <li v-if="!checks.manifest">Build the project first: <code class="bg-blue-100 px-1 rounded">npm run build</code></li>
                        <li v-if="!checks.serviceWorker">Refresh the page to register Service Worker</li>
                        <li v-if="checks.isInstalled">App is already installed. Uninstall to see install prompt again.</li>
                        <li v-if="!checks.canInstall && !checks.isInstalled">Wait a few seconds, then refresh the page</li>
                        <li>Chrome: Check chrome://flags/#enable-desktop-pwas</li>
                        <li>Edge: Check edge://flags/#enable-desktop-pwas</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex gap-4">
                    <button 
                        @click="checkPWARequirements" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        🔄 Recheck Status
                    </button>
                    <a 
                        href="/" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                    >
                        ← Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
