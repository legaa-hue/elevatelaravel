import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import offlineStorage from './offline-storage';
import authService from './auth-service';
import offlineSync from './offline-sync';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Initialize Offline Storage
offlineStorage.init().then(() => {
    console.log('✅ Offline storage initialized');
}).catch(err => {
    console.error('❌ Offline storage initialization failed:', err);
});

// Initialize Auth Service
authService.validateToken().then(valid => {
    if (valid) {
        console.log('✅ Auth token validated');
    }
});

// Initialize Offline Sync System
offlineSync.init().then(() => {
    console.log('✅ Offline sync initialized');
    
    // Try to sync on startup if online
    if (navigator.onLine) {
        offlineSync.syncAll();
    }
}).catch(err => {
    console.error('❌ Offline sync initialization failed:', err);
});

// Register PWA Service Worker manually
console.log('🔧 Attempting to register Service Worker...');

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            console.log('🔧 Window loaded, registering SW from /sw.js');
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });
            
            console.log('✅ Service Worker registered:', registration);
            console.log('✅ SW scope:', registration.scope);
            console.log('✅ SW active:', registration.active);
            console.log('✅ SW installing:', registration.installing);
            console.log('✅ SW waiting:', registration.waiting);
            
            window.swRegistration = registration;
            
            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('🔄 New Service Worker found');
                
                newWorker.addEventListener('statechange', () => {
                    console.log('SW state changed to:', newWorker.state);
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('🔄 New content available, please refresh');
                        if (confirm('New version available! Reload to update?')) {
                            window.location.reload();
                        }
                    }
                });
            });
            
            // Check for updates every hour
            setInterval(() => {
                registration.update();
            }, 60 * 60 * 1000);
            
        } catch (error) {
            console.error('❌ Service Worker registration failed:', error);
        }
    });
} else {
    console.warn('⚠️ Service Workers not supported in this browser');
}
