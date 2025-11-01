<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    success: {
        type: Boolean,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <GuestLayout>
        <Head :title="success ? 'Email Verified' : 'Verification Failed'" />

        <div class="text-center">
            <!-- Success State -->
            <div v-if="success" class="space-y-6">
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ message }}</h2>
                    <p class="mt-3 text-gray-600">
                        Your email <strong>{{ email }}</strong> has been verified.
                    </p>
                    <p class="mt-4 text-lg font-medium text-green-600">
                        ✅ You can now close this tab.
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Return to the registration page to complete your account creation.
                    </p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else class="space-y-6">
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Verification Failed</h2>
                    <p class="mt-3 text-gray-600">{{ message }}</p>
                </div>

                <div class="flex flex-col gap-3">
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-red-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800 focus:bg-red-800 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Back to Registration
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
