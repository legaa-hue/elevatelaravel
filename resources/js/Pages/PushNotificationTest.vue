<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import pushNotificationService from '@/push-notification-service';

const page = usePage();
const user = page.props.auth?.user;

const status = ref({
    supported: false,
    permission: 'default',
    subscribed: false,
    subscription: null,
    vapidKey: null,
});

const loading = ref(false);
const message = ref('');
const messageType = ref('info'); // info, success, error

const checkStatus = async () => {
    status.value.supported = pushNotificationService.isNotificationSupported();
    status.value.permission = pushNotificationService.getPermissionStatus();
    
    if (status.value.supported) {
        const sub = await pushNotificationService.getSubscription();
        status.value.subscribed = !!sub;
        status.value.subscription = sub;
    }
    
    // Check Service Worker status
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.getRegistration();
        console.log('🔍 Service Worker Registration:', registration);
        console.log('🔍 SW State:', registration?.active?.state);
        console.log('🔍 SW Installing:', registration?.installing);
        console.log('🔍 SW Waiting:', registration?.waiting);
        console.log('🔍 SW Active:', registration?.active);
    }
};

const requestPermission = async () => {
    loading.value = true;
    message.value = '';
    
    try {
        const granted = await pushNotificationService.requestPermission();
        if (granted) {
            message.value = 'Permission granted! ✅';
            messageType.value = 'success';
            await checkStatus();
        } else {
            message.value = 'Permission denied ❌';
            messageType.value = 'error';
        }
    } catch (error) {
        message.value = `Error: ${error.message}`;
        messageType.value = 'error';
    } finally {
        loading.value = false;
    }
};

const subscribe = async () => {
    loading.value = true;
    message.value = '';
    
    try {
        console.log('🔄 Starting subscription process...');
        await pushNotificationService.subscribe();
        message.value = 'Successfully subscribed to push notifications! 🎉';
        messageType.value = 'success';
        await checkStatus();
    } catch (error) {
        console.error('❌ Subscription error:', error);
        message.value = `Subscription failed: ${error.message}`;
        messageType.value = 'error';
        
        // Check if it's an auth error
        if (error.message.includes('401') || error.message.includes('Unauthorized')) {
            message.value = 'Authentication failed. Please refresh the page and login again.';
        }
    } finally {
        loading.value = false;
    }
};

const unsubscribe = async () => {
    loading.value = true;
    message.value = '';
    
    try {
        await pushNotificationService.unsubscribe();
        message.value = 'Successfully unsubscribed from push notifications';
        messageType.value = 'success';
        await checkStatus();
    } catch (error) {
        message.value = `Unsubscribe failed: ${error.message}`;
        messageType.value = 'error';
    } finally {
        loading.value = false;
    }
};

const sendTest = async () => {
    loading.value = true;
    message.value = '';
    
    try {
        await pushNotificationService.sendTestNotification(
            'Test Notification',
            'This is a test push notification from ElevateGS! 🚀'
        );
        message.value = 'Test notification sent! Check your notifications.';
        messageType.value = 'success';
    } catch (error) {
        message.value = `Test failed: ${error.message}`;
        messageType.value = 'error';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    checkStatus();
});
</script>

<template>
    <Head title="Push Notification Test" />
    
    <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Push Notification Test</h1>
                
                <!-- Auth Status -->
                <div v-if="user" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-green-600 mr-2">✅</span>
                        <div>
                            <div class="font-medium text-green-900">Logged in as {{ user.name }}</div>
                            <div class="text-sm text-green-700">{{ user.email }} ({{ user.role }})</div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="text-red-600 mr-2">❌</span>
                        <div>
                            <div class="font-medium text-red-900">Not logged in</div>
                            <div class="text-sm text-red-700">
                                Please <a href="/login" class="underline">login</a> to test push notifications
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="mb-8 space-y-4">
                    <h2 class="text-xl font-semibold mb-4">Current Status</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 rounded">
                            <div class="text-sm text-gray-600">Browser Support</div>
                            <div class="text-lg font-medium">
                                <span v-if="status.supported" class="text-green-600">✅ Supported</span>
                                <span v-else class="text-red-600">❌ Not Supported</span>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded">
                            <div class="text-sm text-gray-600">Permission</div>
                            <div class="text-lg font-medium">
                                <span v-if="status.permission === 'granted'" class="text-green-600">✅ Granted</span>
                                <span v-else-if="status.permission === 'denied'" class="text-red-600">❌ Denied</span>
                                <span v-else-if="status.permission === 'default'" class="text-yellow-600">⚠️ Not Asked</span>
                                <span v-else class="text-gray-600">{{ status.permission }}</span>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded">
                            <div class="text-sm text-gray-600">Subscription Status</div>
                            <div class="text-lg font-medium">
                                <span v-if="status.subscribed" class="text-green-600">✅ Subscribed</span>
                                <span v-else class="text-gray-600">❌ Not Subscribed</span>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded">
                            <div class="text-sm text-gray-600">Can Test</div>
                            <div class="text-lg font-medium">
                                <span v-if="status.subscribed" class="text-green-600">✅ Ready</span>
                                <span v-else class="text-gray-600">❌ Subscribe First</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Display -->
                <div v-if="message" class="mb-6 p-4 rounded-lg" :class="{
                    'bg-blue-50 border border-blue-200 text-blue-800': messageType === 'info',
                    'bg-green-50 border border-green-200 text-green-800': messageType === 'success',
                    'bg-red-50 border border-red-200 text-red-800': messageType === 'error'
                }">
                    {{ message }}
                </div>

                <!-- Actions -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold mb-4">Actions</h2>
                    
                    <!-- Step 1: Request Permission -->
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-medium mb-2">Step 1: Request Permission</h3>
                        <p class="text-sm text-gray-600 mb-3">Ask user for notification permission</p>
                        <button
                            @click="requestPermission"
                            :disabled="loading || !status.supported || status.permission === 'granted'"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ loading ? 'Processing...' : 'Request Permission' }}
                        </button>
                    </div>

                    <!-- Step 2: Subscribe -->
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-medium mb-2">Step 2: Subscribe to Push</h3>
                        <p class="text-sm text-gray-600 mb-3">Subscribe to receive push notifications</p>
                        <button
                            @click="subscribe"
                            :disabled="loading || !user || !status.supported || status.permission !== 'granted' || status.subscribed"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ loading ? 'Processing...' : 'Subscribe' }}
                        </button>
                        <div v-if="!user" class="mt-2 text-sm text-red-600">
                            ⚠️ You must be logged in to subscribe
                        </div>
                    </div>

                    <!-- Step 3: Send Test -->
                    <div class="p-4 border rounded-lg">
                        <h3 class="font-medium mb-2">Step 3: Send Test Notification</h3>
                        <p class="text-sm text-gray-600 mb-3">Send a test push notification to yourself</p>
                        <button
                            @click="sendTest"
                            :disabled="loading || !user || !status.subscribed"
                            class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ loading ? 'Sending...' : 'Send Test Notification' }}
                        </button>
                        <div v-if="!user" class="mt-2 text-sm text-red-600">
                            ⚠️ You must be logged in to send notifications
                        </div>
                    </div>

                    <!-- Unsubscribe -->
                    <div class="p-4 border border-red-200 rounded-lg">
                        <h3 class="font-medium mb-2">Unsubscribe</h3>
                        <p class="text-sm text-gray-600 mb-3">Remove push notification subscription</p>
                        <button
                            @click="unsubscribe"
                            :disabled="loading || !user || !status.subscribed"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ loading ? 'Processing...' : 'Unsubscribe' }}
                        </button>
                    </div>
                </div>

                <!-- Subscription Details -->
                <div v-if="status.subscription" class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">Subscription Details</h3>
                    <div class="text-xs font-mono overflow-auto">
                        <div class="mb-2">
                            <strong>Endpoint:</strong>
                            <div class="break-all text-gray-600">{{ status.subscription.endpoint }}</div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-8 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                    <h3 class="font-medium text-blue-900 mb-2">📝 Testing Instructions</h3>
                    <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                        <li v-if="!user" class="font-bold text-red-600">Login first at <a href="/login" class="underline">/login</a></li>
                        <li>Click "Request Permission" and allow notifications</li>
                        <li>Click "Subscribe" to register for push notifications</li>
                        <li>Click "Send Test Notification" to receive a test push</li>
                        <li>Check your browser's notification area</li>
                        <li>Click the notification to test the click handler</li>
                    </ol>
                </div>

                <!-- Troubleshooting -->
                <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                    <h3 class="font-medium text-yellow-900 mb-2">⚠️ Troubleshooting</h3>
                    <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                        <li>Make sure VAPID keys are set in .env</li>
                        <li>Check browser console for errors</li>
                        <li>Ensure you're on HTTPS or localhost</li>
                        <li>Service Worker must be registered</li>
                        <li>Check if notifications are blocked in browser settings</li>
                    </ul>
                </div>

                <!-- Back Button -->
                <div class="mt-8">
                    <a href="/" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 inline-block">
                        ← Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
