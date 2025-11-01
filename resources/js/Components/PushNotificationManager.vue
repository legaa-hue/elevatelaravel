<template>
    <div v-if="isSupported" class="notification-manager">
        <!-- Notification Permission Banner -->
        <div
            v-if="showPermissionBanner"
            class="fixed top-16 left-0 right-0 z-40 bg-blue-600 text-white shadow-lg"
        >
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3 flex-1">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <div>
                            <p class="font-medium">Enable Notifications</p>
                            <p class="text-sm text-blue-100">Stay updated with assignments, grades, and announcements</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="enableNotifications"
                            :disabled="isProcessing"
                            class="px-4 py-2 bg-white text-blue-600 rounded font-medium hover:bg-blue-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isProcessing ? 'Enabling...' : 'Enable' }}
                        </button>
                        <button
                            @click="dismissBanner"
                            class="px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800 transition-colors"
                        >
                            Later
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Status Indicator (in settings or profile) -->
        <div v-if="showStatusIndicator" class="notification-status">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'w-3 h-3 rounded-full',
                            isSubscribed ? 'bg-green-500' : 'bg-gray-400'
                        ]"
                    ></div>
                    <div>
                        <p class="font-medium text-gray-900">Push Notifications</p>
                        <p class="text-sm text-gray-600">
                            {{ isSubscribed ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                </div>
                <button
                    @click="toggleNotifications"
                    :disabled="isProcessing"
                    :class="[
                        'px-4 py-2 rounded font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                        isSubscribed
                            ? 'bg-red-600 text-white hover:bg-red-700'
                            : 'bg-blue-600 text-white hover:bg-blue-700'
                    ]"
                >
                    {{ isProcessing ? 'Processing...' : (isSubscribed ? 'Disable' : 'Enable') }}
                </button>
            </div>

            <!-- Test Notification Button (for debugging) -->
            <button
                v-if="isSubscribed && isDevelopment"
                @click="sendTestNotification"
                class="mt-3 w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
            >
                Send Test Notification
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import pushNotificationService from '@/push-notification-service';

const props = defineProps({
    showBanner: {
        type: Boolean,
        default: true,
    },
    showStatusIndicator: {
        type: Boolean,
        default: false,
    },
});

const isSupported = ref(false);
const showPermissionBanner = ref(false);
const isSubscribed = ref(false);
const isProcessing = ref(false);
const isDevelopment = computed(() => import.meta.env.DEV);

onMounted(async () => {
    isSupported.value = pushNotificationService.isNotificationSupported();
    
    if (!isSupported.value) {
        console.log('ℹ️ Push notifications not supported in this browser');
        return;
    }

    // Check current subscription status
    const subscription = await pushNotificationService.getSubscription();
    isSubscribed.value = !!subscription;

    // Show permission banner if notifications are supported but not enabled
    if (props.showBanner && !isSubscribed.value && !sessionStorage.getItem('notification_banner_dismissed')) {
        const permission = pushNotificationService.getPermissionStatus();
        if (permission === 'default') {
            showPermissionBanner.value = true;
        }
    }
});

const enableNotifications = async () => {
    isProcessing.value = true;
    try {
        await pushNotificationService.subscribe();
        isSubscribed.value = true;
        showPermissionBanner.value = false;
        
        // Show success message
        alert('✅ Push notifications enabled successfully!');
    } catch (error) {
        console.error('Failed to enable notifications:', error);
        alert('❌ Failed to enable push notifications. Please check your browser settings.');
    } finally {
        isProcessing.value = false;
    }
};

const disableNotifications = async () => {
    isProcessing.value = true;
    try {
        await pushNotificationService.unsubscribe();
        isSubscribed.value = false;
        
        // Show success message
        alert('✅ Push notifications disabled successfully.');
    } catch (error) {
        console.error('Failed to disable notifications:', error);
        alert('❌ Failed to disable push notifications.');
    } finally {
        isProcessing.value = false;
    }
};

const toggleNotifications = async () => {
    if (isSubscribed.value) {
        await disableNotifications();
    } else {
        await enableNotifications();
    }
};

const dismissBanner = () => {
    showPermissionBanner.value = false;
    sessionStorage.setItem('notification_banner_dismissed', 'true');
};

const sendTestNotification = async () => {
    try {
        await pushNotificationService.sendTestNotification(
            'Test from ElevateGS',
            'This is a test push notification! 🎉'
        );
        alert('✅ Test notification sent! Check your notifications.');
    } catch (error) {
        console.error('Failed to send test notification:', error);
        alert('❌ Failed to send test notification.');
    }
};
</script>


