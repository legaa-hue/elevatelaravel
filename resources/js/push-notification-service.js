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
            // Clean the key - remove any whitespace, newlines, or invalid characters
            this.publicKey = data.publicKey.trim();
            console.log('📍 Raw VAPID key length:', this.publicKey.length);
            console.log('📍 VAPID key first 20 chars:', this.publicKey.substring(0, 20));
            console.log('📍 VAPID key valid format:', /^[A-Za-z0-9_-]+$/.test(this.publicKey));
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
            console.log('📍 Step 1: Checking existing subscription...');
            // Check if already subscribed
            if (this.subscription) {
                console.log('ℹ️ Already subscribed to push notifications');
                return this.subscription;
            }

            console.log('📍 Step 2: Checking permission...');
            // Request permission if not granted
            if (Notification.permission !== 'granted') {
                const granted = await this.requestPermission();
                if (!granted) {
                    throw new Error('Notification permission denied');
                }
            }
            console.log('✅ Permission granted');

            console.log('📍 Step 3: Getting service worker...');
            // Get service worker registration with multiple strategies
            let registration;
            
            // Strategy 1: Try to get existing registration first
            const existingReg = await navigator.serviceWorker.getRegistration();
            if (existingReg && existingReg.active) {
                console.log('✅ Found existing active Service Worker');
                registration = existingReg;
            } else {
                // Strategy 2: Wait for ready with timeout
                try {
                    registration = await Promise.race([
                        navigator.serviceWorker.ready,
                        new Promise((_, reject) => 
                            setTimeout(() => reject(new Error('Service Worker registration timeout after 10 seconds')), 10000)
                        )
                    ]);
                } catch (error) {
                    // Strategy 3: If timeout, try to get any registration
                    console.warn('⚠️ Timeout waiting for SW ready, trying getRegistration...');
                    registration = await navigator.serviceWorker.getRegistration();
                    if (!registration) {
                        throw new Error('No Service Worker registration found. Please refresh the page.');
                    }
                }
            }
            
            console.log('✅ Service worker ready:', registration.active?.state);

            // Double check the service worker is active
            if (!registration.active) {
                console.warn('⚠️ Service worker not active, waiting...');
                
                // If installing, wait for it
                if (registration.installing) {
                    console.log('⏳ Service Worker is installing, waiting for activation...');
                    await new Promise((resolve) => {
                        registration.installing.addEventListener('statechange', function listener(e) {
                            if (e.target.state === 'activated') {
                                e.target.removeEventListener('statechange', listener);
                                console.log('✅ Service worker now active');
                                resolve();
                            }
                        });
                    });
                } else if (registration.waiting) {
                    console.log('⏳ Service Worker is waiting, trying to activate...');
                    // Try to skip waiting
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                    await new Promise(resolve => setTimeout(resolve, 1000));
                } else {
                    throw new Error('Service Worker is not active and not installing. Please refresh the page.');
                }
            }

            console.log('📍 Step 4: Fetching VAPID public key...');
            // Fetch VAPID public key if not already fetched
            if (!this.publicKey) {
                await this.fetchPublicKey();
            }
            console.log('✅ VAPID key fetched:', this.publicKey?.substring(0, 20) + '...');

            console.log('📍 Step 5: Subscribing to push manager...');
            // Subscribe to push notifications
            console.log('📍 Converting key to Uint8Array...');
            const convertedKey = this.urlBase64ToUint8Array(this.publicKey);
            console.log('📍 Converted key length:', convertedKey.length, 'bytes');
            console.log('📍 Converted key sample:', Array.from(convertedKey.slice(0, 10)));
            
            this.subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedKey,
            });
            console.log('✅ Browser subscription created');

            console.log('📍 Step 6: Sending subscription to server...');
            // Send subscription to server
            await this.sendSubscriptionToServer(this.subscription);
            console.log('✅ Server subscription saved');

            console.log('✅ Successfully subscribed to push notifications');
            return this.subscription;
        } catch (error) {
            console.error('❌ Failed to subscribe to push notifications:', error);
            console.error('Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
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
        console.log('📤 Sending subscription to server...');
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        // Get CSRF token for Laravel session-based requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        // Add JWT token if available (for API requests)
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
            console.log('🔑 Using JWT token');
        }
        
        // Add CSRF token if available (for web requests)
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
            console.log('🔑 Using CSRF token');
        } else {
            console.warn('⚠️ No CSRF token found!');
        }
        
        const payload = {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
            },
        };
        
        console.log('📤 Request headers:', headers);
        console.log('📤 Request payload endpoint:', payload.endpoint.substring(0, 50) + '...');
        
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers,
            credentials: 'same-origin', // Include cookies for session auth
            body: JSON.stringify(payload),
        });

        console.log('📥 Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Server error response:', errorText);
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { message: errorText };
            }
            throw new Error(errorData.message || `Server error: ${response.status}`);
        }

        const result = await response.json();
        console.log('✅ Server response:', result);
        return result;
    }

    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(subscription) {
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        // Get CSRF token for Laravel session-based requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        // Add JWT token if available (for API requests)
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Add CSRF token if available (for web requests)
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        const response = await fetch('/api/push/unsubscribe', {
            method: 'POST',
            headers,
            credentials: 'same-origin', // Include cookies for session auth
            body: JSON.stringify({
                endpoint: subscription.endpoint,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to remove subscription from server');
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
        console.log('📤 Sending test notification...');
        const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
        
        // Get CSRF token for Laravel session-based requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        console.log('🔑 Auth tokens:', {
            hasJWT: !!token,
            hasCSRF: !!csrfToken,
        });
        
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        // Add JWT token if available (for API requests)
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
            console.log('🔑 Using JWT token');
        }
        
        // Add CSRF token if available (for web requests)
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
            console.log('🔑 Using CSRF token');
        } else {
            console.warn('⚠️ No CSRF token found!');
        }
        
        console.log('📤 Request headers:', headers);
        
        const response = await fetch('/api/push/test', {
            method: 'POST',
            headers,
            credentials: 'same-origin', // Include cookies for session auth
            body: JSON.stringify({ title, body }),
        });

        console.log('📥 Response status:', response.status, response.statusText);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error('❌ Error response:', errorData);
            throw new Error(errorData.message || 'Failed to send test notification');
        }

        const result = await response.json();
        console.log('✅ Test notification sent successfully:', result);
        return result;
    }
}

// Create singleton instance
const pushNotificationService = new PushNotificationService();

export default pushNotificationService;
