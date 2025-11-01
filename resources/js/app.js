import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import offlineStorage from './offline-storage';
import authService from './auth-service';

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

// Register PWA Service Worker
import { registerSW } from 'virtual:pwa-register';

const updateSW = registerSW({
    onNeedRefresh() {
        console.log('🔄 New content available, please refresh.');
        // Optionally show a prompt to user
        if (confirm('New version available! Reload to update?')) {
            updateSW(true);
        }
    },
    onOfflineReady() {
        console.log('✅ App ready to work offline');
    },
    onRegistered(registration) {
        console.log('✅ Service Worker registered');
        
        // Check for updates every hour
        setInterval(() => {
            registration.update();
        }, 60 * 60 * 1000);
    },
});
