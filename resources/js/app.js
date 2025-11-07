import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { router } from '@inertiajs/vue3';
import offlineStorage from './offline-storage';
import authService from './auth-service';
import offlineSync from './offline-sync';
import offlineNavigation from './offline-navigation';
import stateRehydration from './state-rehydration';
import syncQueue from './sync-queue';
import inertiaOfflineHandler from './inertia-offline-handler-axios'; // NEW AXIOS-BASED HANDLER
import { prefetchCorePages } from './offline-prefetch';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Swallow Vite preload CSS errors when offline to avoid crashing dynamic imports
try {
    window.addEventListener('vite:preloadError', (evt) => {
        if (!navigator.onLine) {
            evt.preventDefault();
            const msg = (evt && evt.payload && evt.payload.message) || 'preload error';
            console.warn('🟡 Ignoring preload error while offline:', msg);
        }
    });
} catch {}

// Initialize the offline handler BEFORE creating the app
inertiaOfflineHandler.init().then(() => {
    console.log('✅ Inertia offline handler ready');
}).catch(err => {
    console.error('❌ Inertia offline handler initialization failed:', err);
});

// Cache pages after successful Inertia navigations (online sessions)
router.on('success', async (event) => {
    try {
        const page = event?.detail?.page;
        if (page && page.component) {
            await offlineStorage.savePageData(page);
        }
    } catch (e) {
        console.warn('Failed to cache page after success:', e);
    }
});

// Create a fallback component for offline mode when component is not cached
function createFallbackComponent(componentName) {
    return {
        default: {
            name: 'OfflineFallback',
            template: `
                <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
                    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6 text-center">
                        <div class="mb-4">
                            <span class="material-icons text-6xl text-gray-400">cloud_off</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">Page Not Available Offline</h1>
                        <p class="text-gray-600 mb-4">
                            The page "${componentName}" hasn't been cached yet and is not available offline.
                        </p>
                        <p class="text-sm text-gray-500 mb-6">
                            Please connect to the internet and visit this page while online to cache it for offline use.
                        </p>
                        <button
                            @click="$inertia.visit('/')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Go to Dashboard
                        </button>
                    </div>
                </div>
            `
        }
    };
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        try {
            // When offline, check component cache first
            if (!navigator.onLine && inertiaOfflineHandler) {
                const cached = inertiaOfflineHandler.getCachedComponent(name);
                if (cached) {
                    console.log(`📦 Using offline cached component: ${name}`);
                    return cached;
                }

                // Check if this component has failed before
                if (inertiaOfflineHandler.hasComponentFailed(name)) {
                    console.warn(`⚠️ Component ${name} previously failed to load, returning fallback`);
                    return createFallbackComponent(name);
                }

                console.warn(`⚠️ Component ${name} not in offline cache, attempting to load...`);
            }

            // Mark as loading
            if (inertiaOfflineHandler) {
                inertiaOfflineHandler.markComponentLoading(name);
            }

            // Online or not cached - resolve normally
            const component = await resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            );

            // Cache it for offline use
            if (inertiaOfflineHandler) {
                inertiaOfflineHandler.cacheComponent(name, component);
                inertiaOfflineHandler.markComponentLoadingComplete(name);
            }

            return component;
        } catch (error) {
            console.error(`❌ Failed to resolve component ${name}:`, error);

            // Mark component as failed
            if (inertiaOfflineHandler) {
                inertiaOfflineHandler.markComponentFailed(name);
            }

            // If offline and component doesn't exist, return fallback
            if (!navigator.onLine) {
                return createFallbackComponent(name);
            }

            throw error;
        }
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
        // Cache the initial page as well (first load)
        try {
            if (props && props.initialPage) {
                offlineStorage.savePageData(props.initialPage);
            }
        } catch {}

        // Opportunistic prefetch of core pages for offline
        try {
            const role = (props && props.initialPage && props.initialPage.props && props.initialPage.props.auth && props.initialPage.props.auth.user && props.initialPage.props.auth.user.role) || undefined;
            const version = (props && props.initialPage && props.initialPage.version) || undefined;
            // Delay a tick to avoid blocking initial render
            setTimeout(() => prefetchCorePages({ role, version }).catch(() => {}), 500);
        } catch {}

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// Initialize Offline Storage FIRST (required by other services)
offlineStorage.init().then(() => {
    console.log('✅ Offline storage initialized');
    // Start offline/online banner UI
    try { offlineNavigation.init(); } catch {}
}).catch(err => {
    console.error('❌ Offline storage initialization failed:', err);
});

// Initialize Auth Service
authService.validateToken().then(valid => {
    if (valid) {
        console.log('✅ Auth token validated');
    }
});

// (Removed duplicate init of Inertia Offline Handler)

// Initialize Offline Sync System
offlineSync.init().then(async () => {
    console.log('✅ Offline sync initialized');
    
    // Try to sync on startup if online
    if (navigator.onLine) {
        offlineSync.syncAll();
    }

    // Update offline banner pending count once on init
    try {
        const count = await offlineSync.getPendingCount();
        if (typeof offlineNavigation?.setPendingCount === 'function') {
            offlineNavigation.setPendingCount(count);
        }
    } catch {}
}).catch(err => {
    console.error('❌ Offline sync initialization failed:', err);
});

// Register PWA Service Worker manually
console.log('🔧 Attempting to register Service Worker...');

// Listen for messages from the Service Worker (loader errors etc.)
if ('serviceWorker' in navigator) {
    try {
        navigator.serviceWorker.addEventListener('message', (event) => {
            const data = event && event.data;
            if (!data) return;
            if (data.type === 'SW_LOADER_ERROR') {
                console.error('❌ [SW->page] Loader error:', data.message, data.stack || '');
            } else if (data.type === 'SW_LOADER_INFO') {
                console.log('ℹ️ [SW->page]', data.message);
            } else if (data.type === 'SW_NAV') {
                console.log(`🧭 [SW NAV] path=${data.path} hit=${data.hit}`);
            }
        });
    } catch {}
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const swUrl = `/sw.js?v=${Date.now()}`; // cache-bust
            console.log('🔧 Window loaded, registering SW from', swUrl);
            const registration = await navigator.serviceWorker.register(swUrl, {
                scope: '/'
            });
            
            console.log('✅ Service Worker registered:', registration);
            console.log('✅ SW scope:', registration.scope);
            console.log('✅ SW active:', registration.active);
            console.log('✅ SW installing:', registration.installing);
            console.log('✅ SW waiting:', registration.waiting);
            
            window.swRegistration = registration;
            
            // Warm runtime caches with Vite manifest (helps even before new SW activates)
            try {
                if (navigator.onLine && 'caches' in window) {
                    const res = await fetch('/build/manifest.json', { cache: 'no-store' });
                    if (res.ok) {
                        const manifest = await res.json();
                        const seen = new Set();
                        const urls = [];
                        const add = (p) => { if (p && !seen.has(p)) { seen.add(p); urls.push(p); } };
                        const toAbs = (p) => p.startsWith('/build/') ? p : `/build/${String(p).replace(/^\/?/, '')}`;
                        const visit = (entry) => {
                            if (!entry) return;
                            if (entry.file) add(toAbs(entry.file));
                            if (Array.isArray(entry.css)) entry.css.forEach(c => add(toAbs(c)));
                            if (Array.isArray(entry.assets)) entry.assets.forEach(a => add(toAbs(a)));
                            if (Array.isArray(entry.imports)) entry.imports.forEach(k => visit(manifest[k]));
                        };
                        Object.values(manifest).forEach(visit);
                        const jsUrls = urls.filter(u => /\.js(\?.*)?$/i.test(u));
                        const cssUrls = urls.filter(u => /\.css(\?.*)?$/i.test(u));
                        const jsCache = await caches.open('js-modules-cache-v1');
                        const cssCache = await caches.open('css-cache-v1');
                        try { await jsCache.addAll(jsUrls); } catch {}
                        try { await cssCache.addAll(cssUrls); } catch {}
                        console.log(`🔥 Warmed caches: js=${jsUrls.length}, css=${cssUrls.length}`);
                    }
                }
            } catch {}
            
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
            // Inspect existing registrations to detect duplicates or wrong scopes
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                if (regs && regs.length) {
                    console.groupCollapsed(`🔎 Found ${regs.length} service worker registration(s)`);
                    regs.forEach((r, idx) => {
                        console.log(`#${idx+1}`, {
                            scope: r.scope,
                            active: r.active && r.active.scriptURL,
                            installing: r.installing && r.installing.scriptURL,
                            waiting: r.waiting && r.waiting.scriptURL,
                        });
                    });
                    console.groupEnd();
                } else {
                    console.log('🔎 No existing SW registrations');
                }
            } catch {}
        }
    });
} else {
    console.warn('⚠️ Service Workers not supported in this browser');
}
