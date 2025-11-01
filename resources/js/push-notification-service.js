/**
 * Push Notification Service
 * Handles browser push notification subscriptions and messaging
 */

class PushNotificationService {
    constructor() {
        this.publicKey = null;
        this.subscription = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
    }

    /**
     * Check if push notifications are supported
     */
    isNotificationSupported() {
        return this.isSupported;
    }

    /**
     * Get current notification permission status
     */
    getPermissionStatus() {
        if (!this.isSupported) return 'unsupported';
        return Notification.permission;
    }

    /**
     * Request notification permission from user
     */
    async requestPermission() {
        if (!this.isSupported) {
            throw new Error('Push notifications are not supported in this browser');
        }

        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('✅ Notification permission granted');
            return true;
        } else if (permission === 'denied') {
            console.log('❌ Notification permission denied');
            return false;
        } else {
            console.log('⚠️ Notification permission dismissed');
            return false;
        }
    }

    /**
     * Fetch VAPID public key from server
     */
    async fetchPublicKey() {
        try {
            const response = await fetch('/api/push/public-key');
            const data = await response.json();
            this.publicKey = data.publicKey;
            return this.publicKey;
        } catch (error) {
            console.error('❌ Failed to fetch VAPID public key:', error);
            throw error;
        }
    }

    /**
     * Convert base64 string to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Subscribe user to push notifications
     */
    async subscribe() {
        try {
            // Check if already subscribed
            if (this.subscription) {
                console.log('ℹ️ Already subscribed to push notifications');
                return this.subscription;
            }

            // Request permission if not granted
            if (Notification.permission !== 'granted') {
                const granted = await this.requestPermission();
                if (!granted) {
                    throw new Error('Notification permission denied');
                }
            }

            // Get service worker registration
            const registration = await navigator.serviceWorker.ready;

            // Fetch VAPID public key if not already fetched
            if (!this.publicKey) {
                await this.fetchPublicKey();
            }

            // Subscribe to push notifications
            const convertedKey = this.urlBase64ToUint8Array(this.publicKey);
            this.subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedKey,
            });

            // Send subscription to server
            await this.sendSubscriptionToServer(this.subscription);

            console.log('✅ Successfully subscribed to push notifications');
            return this.subscription;
        } catch (error) {
            console.error('❌ Failed to subscribe to push notifications:', error);
            throw error;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        try {
            if (!this.subscription) {
                const registration = await navigator.serviceWorker.ready;
                this.subscription = await registration.pushManager.getSubscription();
            }

            if (this.subscription) {
                // Remove subscription from server
                await this.removeSubscriptionFromServer(this.subscription);

                // Unsubscribe from browser
                await this.subscription.unsubscribe();
                this.subscription = null;

                console.log('✅ Successfully unsubscribed from push notifications');
                return true;
            }

            return false;
        } catch (error) {
            console.error('❌ Failed to unsubscribe from push notifications:', error);
            throw error;
        }
    }

    /**
     * Send subscription details to server
     */
    async sendSubscriptionToServer(subscription) {
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                    auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                },
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to save subscription on server');
        }

        return await response.json();
    }

    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(subscription) {
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        const response = await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to remove subscription from server');
        }

        return await response.json();
    }

    /**
     * Get current subscription
     */
    async getSubscription() {
        if (this.subscription) {
            return this.subscription;
        }

        if (!this.isSupported) {
            return null;
        }

        const registration = await navigator.serviceWorker.ready;
        this.subscription = await registration.pushManager.getSubscription();
        return this.subscription;
    }

    /**
     * Send test notification
     */
    async sendTestNotification(title = 'Test Notification', body = 'This is a test from ElevateGS!') {
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        const response = await fetch('/api/push/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ title, body }),
        });

        if (!response.ok) {
            throw new Error('Failed to send test notification');
        }

        return await response.json();
    }
}

// Create singleton instance
const pushNotificationService = new PushNotificationService();

export default pushNotificationService;
