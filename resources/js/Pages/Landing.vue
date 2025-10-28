<template>
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="border-b border-gray-200 bg-white sticky top-0 z-50">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <span class="text-2xl font-medium">
                            <span class="text-gray-900">Elevate</span><span class="text-red-900">GS</span>
                        </span>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-gray-700 hover:text-red-900 transition-colors"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-gray-700 hover:text-red-900 transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center px-5 py-2 text-sm font-medium text-white bg-red-900 rounded hover:bg-red-800 transition-colors"
                            >
                                Get started
                            </Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main>
            <!-- Hero -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
                <h1 class="text-5xl sm:text-6xl font-medium text-gray-900 mb-6 tracking-tight leading-tight">
                    Learning Management
                    <br>
                    <span class="text-red-900">Made Simple</span>
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                    ElevateGS is a modern Learning Management System for USANT GradSchool. 
                    Access your courses anytime, anywhere, on any device.
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('login')"
                        class="inline-flex items-center px-8 py-3 text-base font-medium text-white bg-red-900 rounded hover:bg-red-800 transition-colors shadow-sm"
                    >
                        Get started for free
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center px-8 py-3 text-base font-medium text-white bg-red-900 rounded hover:bg-red-800 transition-colors shadow-sm"
                    >
                        Go to Dashboard
                    </Link>
                    <button
                        v-if="showInstallPrompt"
                        @click="installPWA"
                        class="inline-flex items-center px-8 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Install App
                    </button>
                </div>

                <!-- Status Badges -->
                <div class="flex flex-wrap gap-3 justify-center mb-16">
                    <div v-if="isInstalled" class="inline-flex items-center px-4 py-2 bg-green-50 rounded-full">
                        <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-green-800">App Installed</span>
                    </div>
                    <div v-if="isOfflineReady" class="inline-flex items-center px-4 py-2 bg-blue-50 rounded-full">
                        <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 3.636a1 1 0 010 1.414 7 7 0 000 9.9 1 1 0 11-1.414 1.414 9 9 0 010-12.728 1 1 0 011.414 0zm9.9 0a1 1 0 011.414 0 9 9 0 010 12.728 1 1 0 11-1.414-1.414 7 7 0 000-9.9 1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-blue-800">Works Offline</span>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 bg-gray-50">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-medium text-gray-900 mb-4">Built for modern learning</h2>
                    <p class="text-lg text-gray-600">Everything you need in one place</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-yellow-500"></div>
                        <div class="p-8">
                            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-gray-900 mb-3">Cross-Platform</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Works seamlessly on Android, iOS, iPhone, Mac, and Windows. One platform, all devices.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-red-900"></div>
                        <div class="p-8">
                            <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-gray-900 mb-3">Lightning Fast</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Progressive Web App technology ensures instant loading and smooth navigation.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-2 bg-yellow-500"></div>
                        <div class="p-8">
                            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-medium text-gray-900 mb-3">Always Available</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Access your courses offline. Syncs automatically when you're back online.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Responsive Design Section -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-medium text-gray-900 mb-4">Responsive on every device</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                        Whether you're on your phone during your commute or at your desk, 
                        ElevateGS adapts perfectly to your screen.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-1 bg-red-900"></div>
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Android</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-1 bg-yellow-500"></div>
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">iOS/iPhone</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-1 bg-red-900"></div>
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Mac</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-1 bg-yellow-500"></div>
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Windows</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-1 bg-red-900"></div>
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Tablet</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- For USANT GradSchool Section -->
            <section class="bg-red-900 text-white py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-4xl font-medium mb-4">For USANT GradSchool</h2>
                    <p class="text-xl text-red-100 max-w-2xl mx-auto mb-8 leading-relaxed">
                        A comprehensive Learning Management System designed specifically 
                        for graduate students and faculty.
                    </p>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('login')"
                        class="inline-flex items-center px-8 py-3 text-base font-medium text-red-900 bg-yellow-400 rounded hover:bg-yellow-300 transition-colors"
                    >
                        Join ElevateGS Today
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="text-center">
                    <p class="text-sm text-gray-500 font-light">
                        &copy; 2025 ElevateGS. Learning Management System for USANT GradSchool.
                    </p>
                    <div class="mt-4 flex justify-center space-x-6 text-sm text-gray-500">
                        <span class="font-light">PWA</span>
                        <span class="font-light">•</span>
                        <span class="font-light">SPA</span>
                        <span class="font-light">•</span>
                        <span class="font-light">Cross-Platform</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const showInstallPrompt = ref(false);
const isInstalled = ref(false);
const isOfflineReady = ref(false);
let deferredPrompt = null;

onMounted(() => {
    // Check if app is already installed
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true;
    }

    // Listen for PWA install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPrompt.value = true;
    });

    // Listen for app installed event
    window.addEventListener('appinstalled', () => {
        showInstallPrompt.value = false;
        isInstalled.value = true;
        console.log('ElevateGS PWA was installed');
    });

    // Listen for offline ready event from service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(() => {
            isOfflineReady.value = true;
        });
    }
});

const installPWA = async () => {
    if (!deferredPrompt) return;
    
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    
    console.log(`User response to the install prompt: ${outcome}`);
    deferredPrompt = null;
    showInstallPrompt.value = false;
};
</script>
