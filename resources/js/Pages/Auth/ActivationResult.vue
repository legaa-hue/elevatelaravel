<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    success: {
        type: Boolean,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    canResend: {
        type: Boolean,
        default: false,
    },
    email: {
        type: String,
        default: '',
    },
});

const countdown = ref(3);
const redirecting = ref(false);

onMounted(() => {
    if (props.success) {
        // Start countdown for successful activation
        const interval = setInterval(() => {
            countdown.value--;
            if (countdown.value === 0) {
                clearInterval(interval);
                redirecting.value = true;
                router.visit(route('login'));
            }
        }, 1000);
    }
});

const goToLogin = () => {
    router.visit(route('login'));
};

const resendActivation = () => {
    router.post(route('account.resend'), { email: props.email }, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Activation email has been resent. Please check your inbox.');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="success ? 'Account Activated' : 'Activation Failed'" />

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
                        Redirecting to Login in <span class="font-bold text-green-600">{{ countdown }}</span>...
                    </p>
                </div>

                <PrimaryButton
                    @click="goToLogin"
                    class="mt-4 justify-center"
                    :disabled="redirecting"
                >
                    Go to Login Now
                </PrimaryButton>
            </div>

            <!-- Error State -->
            <div v-else class="space-y-6">
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Activation Failed</h2>
                    <p class="mt-3 text-gray-600">{{ message }}</p>
                </div>

                <div class="flex flex-col gap-3">
                    <PrimaryButton
                        v-if="canResend"
                        @click="resendActivation"
                        class="justify-center"
                    >
                        Resend Activation Email
                    </PrimaryButton>

                    <Link
                        :href="route('login')"
                        class="inline-block text-sm text-gray-600 hover:text-gray-900 underline"
                    >
                        Back to Login
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
