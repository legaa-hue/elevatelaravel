/**
 * Inertia Offline Handler
 * Intercepts Inertia requests when offline and serves cached page data
 */

import { router } from '@inertiajs/vue3';
import offlineStorage from './offline-storage';

class InertiaOfflineHandler {
    constructor() {
        this.initialized = false;
        this.pendingOfflinePage = null;
    }

    /**
     * Initialize the offline handler
     */
    async init() {
        if (this.initialized) return;

        console.log('🔧 Initializing Inertia Offline Handler');
        
        // Intercept clicks on links when offline
        document.addEventListener('click', async (e) => {
            // Only handle when offline
            if (navigator.onLine) return;
            
            // Find if clicked element is or is within a link
            const link = e.target.closest('a[href]');
            if (!link) return;
            
            const url = link.href;
            
            // Only handle internal links
            if (!url.startsWith(window.location.origin)) return;
            
            console.log('🔍 Offline click on:', url);
            
            // Check if we have cached data
            const cachedPage = await this.getCachedPageData(url);
            
            if (cachedPage) {
                console.log('✅ Found cached page, rendering...');
                console.log('Component:', cachedPage.component);
                
                // Prevent default navigation
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Update browser URL FIRST
                window.history.pushState({}, '', url);
                
                // Use the success event handler to inject our cached page
                // This is how Inertia updates the page internally
                const handleSuccess = (event) => {
                    // Remove this one-time handler
                    router.off('success', handleSuccess);
                };
                
                router.on('success', handleSuccess);
                
                // Manually fire the Inertia navigate event with our cached data
                // This will trigger all the proper Inertia lifecycle hooks
                window.dispatchEvent(new CustomEvent('inertia:navigate', {
                    bubbles: true,
                    detail: { page: cachedPage }
                }));
                
                // Also dispatch success event that components listen to
                window.dispatchEvent(new CustomEvent('inertia:success', {
                    bubbles: true,
                    detail: { page: cachedPage }
                }));
                
                // Force a page update by manually setting the Inertia page
                // Access the internal page store that Inertia uses
                if (window.$inertiaPage) {
                    Object.assign(window.$inertiaPage, cachedPage);
                }
                
                console.log('✅ Offline page rendered');
            } else {
                console.log('❌ No cached page found');
                e.preventDefault();
                alert('This page is not available offline');
            }
        }, true); // Use capture to intercept before Inertia

        this.initialized = true;
        console.log('✅ Inertia offline handler initialized');
    }

    /**
     * Get cached page data from IndexedDB
     */
    async getCachedPageData(url) {
        try {
            // Normalize URL (remove query params and hash for cache key)
            const urlObj = new URL(url, window.location.origin);
            const cacheKey = urlObj.pathname;
            
            // Try to get from visitedPages store
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
     * Preload critical pages for offline use
     */
    async preloadPages(urls) {
        if (!navigator.onLine) {
            console.warn('Cannot preload - offline');
            return;
        }

        console.log(`📦 Preloading ${urls.length} pages...`);

        for (const url of urls) {
            try {
                // Make Inertia request to cache the page
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
