// Custom Service Worker for ElevateGS PWA
// This file extends the Vite PWA generated service worker

// Handle push events
self.addEventListener('push', event => {
    console.log('[Service Worker] Push received:', event);

    if (!event.data) {
        console.log('[Service Worker] Push event but no data');
        return;
    }

    try {
        const data = event.data.json();
        console.log('[Service Worker] Push data:', data);

        const title = data.title || 'ElevateGS';
        const options = {
            body: data.body || 'You have a new notification',
            icon: data.icon || '/build/assets/icon-192x192.png',
            badge: data.badge || '/build/assets/badge-72x72.png',
            image: data.image,
            vibrate: data.vibrate || [200, 100, 200],
            data: {
                url: data.data?.url || '/dashboard',
                ...data.data,
            },
            actions: data.actions || [
                {
                    action: 'view',
                    title: 'View',
                    icon: '/build/assets/icon-view.png'
                },
                {
                    action: 'close',
                    title: 'Close',
                    icon: '/build/assets/icon-close.png'
                }
            ],
            tag: data.tag || 'elevategs-notification',
            requireInteraction: data.requireInteraction || false,
            renotify: data.renotify || false,
            silent: data.silent || false,
        };

        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    } catch (error) {
        console.error('[Service Worker] Error handling push:', error);
    }
});

// Handle notification click
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notification clicked:', event);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Check if there's already a window open
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open a new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Handle notification close
self.addEventListener('notificationclose', event => {
    console.log('[Service Worker] Notification closed:', event);
    
    // Track notification close analytics here if needed
});

// Handle background sync for failed push notifications
self.addEventListener('sync', event => {
    if (event.tag === 'sync-push-subscriptions') {
        event.waitUntil(
            // Sync any pending subscription updates
            syncPushSubscriptions()
        );
    } else if (event.tag === 'sync-offline-data') {
        // Trigger offline data sync in the main app
        event.waitUntil(
            self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    client.postMessage({
                        type: 'SYNC_OFFLINE_DATA'
                    });
                });
            })
        );
    }
});

async function syncPushSubscriptions() {
    console.log('[Service Worker] Syncing push subscriptions');
    // Implementation for syncing subscriptions when back online
}

// Handle push subscription change
self.addEventListener('pushsubscriptionchange', event => {
    console.log('[Service Worker] Push subscription changed');
    
    event.waitUntil(
        // Resubscribe to push notifications
        self.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: event.oldSubscription?.options?.applicationServerKey
        }).then(subscription => {
            // Send new subscription to server
            return fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')))),
                    },
                }),
            });
        })
    );
});

console.log('[Service Worker] Push notification handlers registered');
