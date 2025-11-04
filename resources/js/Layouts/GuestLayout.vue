<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InstallPWAPrompt from '@/Components/InstallPWAPrompt.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Install PWA Prompt -->
    <InstallPWAPrompt />
    
    <div class="flex min-h-screen flex-col items-center bg-white pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-auto" />
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white border border-gray-200 rounded-lg px-6 py-8 shadow-sm sm:max-w-md">
            <div class="h-2 bg-red-900 -mx-6 -mt-8 mb-8 rounded-t-lg"></div>
            <slot />
        </div>
    </div>
</template>
