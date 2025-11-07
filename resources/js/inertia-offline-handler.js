/**
 * Inertia Offline Handler - Simple Approach
 * Like Google Drive: Don't make requests offline, just swap the page directly
 */

import { router } from '@inertiajs/vue3';
import offlineStorage from './offline-storage';

class InertiaOfflineHandler {
    constructor() {
        this.initialized = false;
        this.componentCache = new Map();
        this.swapComponent = null; // Will be set by Inertia
    }

    /**
     * Cache a resolved component module
     */
    cacheComponent(name, componentModule) {
        this.componentCache.set(name, componentModule);
        console.log(`💾 Cached component: ${name}`);
    }
    
    /**
     * Get cached component module
     */
    getCachedComponent(name) {
        return this.componentCache.get(name);
    }

    /**
     * Initialize the offline handler
     */
    async init() {
        if (this.initialized) return;

        console.log('🔧 Initializing Inertia Offline Handler');
        
        const self = this;
        
        // Simple approach: Intercept navigation and manually update page
        router.on('before', async (event) => {
            // Only handle when offline
            if (navigator.onLine) return;
            
            const url = event.detail.visit.url.href || event.detail.visit.url;
            console.log('🔍 Offline navigation to:', url);
            
            // Prevent the navigation
            event.preventDefault();
            
            try {
                // Get cached page data
                const cachedPage = await self.getCachedPageData(url);
                
                if (!cachedPage) {
                    console.log('❌ No cached page');
                    alert('This page is not available offline');
                    return;
                }
                
                console.log('✅ Found cached page:', cachedPage.component);
                
                // Check if component is cached
                const component = self.getCachedComponent(cachedPage.component);
                if (!component) {
                    console.error(`❌ Component ${cachedPage.component} not cached`);
                    alert('This page cannot be displayed offline. Please visit it online first.');
                    return;
                }
                
                console.log('📦 Component ready');
                
                // Create page object
                const page = {
                    component: cachedPage.component,
                    props: cachedPage.props || {},
                    url: url,
                    version: cachedPage.version || null
                };
                
                // Update URL first
                window.history.pushState({}, '', url);
                
                // Try to access Vue's internal reactive refs
                // The page data is stored in a reactive ref in the Inertia app
                try {
                    // Get all Vue app instances
                    const vueApps = document.querySelectorAll('[data-page]');
                    if (vueApps.length > 0) {
                        const appEl = vueApps[0];
                        // Access the Vue instance via __vue_app__
                        if (appEl.__vue_app__) {
                            const app = appEl.__vue_app__;
                            // The page is stored in the root component's props
                            const rootComponent = app._instance;
                            if (rootComponent && rootComponent.props && rootComponent.props.initialPage) {
                                // Update the initial page
                                Object.assign(rootComponent.props.initialPage, page);
                                console.log('✅ Updated via Vue instance');
                                return;
                            }
                        }
                    }
                    
                    // Fallback: Use router's internal page setter
                    // Force update by triggering a manual page swap
                    const customEvent = new CustomEvent('inertia:before-swap', { 
                        detail: { page },
                        cancelable: false 
                    });
                    document.dispatchEvent(customEvent);
                    
                    console.log('✅ Dispatched page swap event');
                } catch (err) {
                    console.error('❌ Failed to swap page:', err);
                }
                
            } catch (error) {
                console.error('❌ Offline navigation failed:', error);
                alert('Failed to load offline page');
            }
        });

        this.initialized = true;
        console.log('✅ Inertia offline handler ready');
    }

    /**
     * Get cached page data from IndexedDB
     */
    async getCachedPageData(url) {
        try {
            const urlObj = new URL(url, window.location.origin);
            const cacheKey = urlObj.pathname;
            
            const cached = await offlineStorage.get('visitedPages', cacheKey);
            
            if (cached && cached.component) {
                return {
                    component: cached.component,
                    props: cached.props || {},
                    url: cacheKey,
                    version: cached.version
                };
            }
            
            return null;
        } catch (error) {
            console.error('Failed to get cached page:', error);
            return null;
        }
    }

    /**
     * Preload pages for offline use
     */
    async preloadPages(urls) {
        if (!navigator.onLine) {
            console.warn('Cannot preload - offline');
            return;
        }

        console.log(`📦 Preloading ${urls.length} pages...`);

        for (const url of urls) {
            try {
                await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Inertia': 'true',
                        'X-Inertia-Version': document.querySelector('meta[name="inertia-version"]')?.content || ''
                    }
                });

                console.log(`✅ Preloaded: ${url}`);
            } catch (error) {
                console.warn(`Failed to preload ${url}:`, error);
            }
        }
    }
}

// Create singleton
const inertiaOfflineHandler = new InertiaOfflineHandler();

// Make globally available
if (typeof window !== 'undefined') {
    window.inertiaOfflineHandler = inertiaOfflineHandler;
}

export default inertiaOfflineHandler;
