/**
 * Inertia Offline Handler - HTTP Interception Approach
 * Intercept Axios/XHR requests and return cached responses
 * This makes Inertia think it got a real server response
 */

import axios from 'axios';
import offlineStorage from './offline-storage';

class InertiaOfflineHandlerAxios {
    constructor() {
        this.componentCache = new Map();
        this.interceptorId = null;
    }

    /**
     * Cache a resolved component
     */
    cacheComponent(name, componentModule) {
        this.componentCache.set(name, componentModule);
        console.log(`💾 Cached component: ${name}`);
    }
    
    /**
     * Get cached component
     */
    getCachedComponent(name) {
        return this.componentCache.get(name);
    }

    /**
     * Initialize Axios interceptor
     */
    init() {
        console.log('🔧 Initializing Axios Offline Handler');
        const self = this;

        // Intercept ALL axios requests
        this.interceptorId = axios.interceptors.request.use(
            async (config) => {
                // Only intercept Inertia GET requests when offline
                if (!navigator.onLine && 
                    config.headers && 
                    config.headers['X-Inertia'] && 
                    config.method === 'get') {
                    
                    console.log('🔌 Intercepting offline request:', config.url);

                    try {
                        // Build full URL (offlineStorage will normalize)
                        const fullUrl = new URL(config.url, window.location.origin).href;
                        // Get cached page data
                        const cachedPage = await self.getCachedPageData(fullUrl);

                        if (cachedPage) {
                            console.log('✅ Serving cached page:', cachedPage.component);

                            // Create a fake Axios error that contains our response
                            // We have to throw an error to prevent the real request
                            const fakeResponse = {
                                data: JSON.stringify(cachedPage), // Inertia expects JSON string
                                status: 200,
                                statusText: 'OK',
                                headers: {
                                    'x-inertia': 'true',
                                    'content-type': 'application/json',
                                    'x-inertia-version': cachedPage.version || ''
                                },
                                config,
                                request: {}
                            };

                            // Throw a special error that response interceptor will catch
                            throw {
                                response: fakeResponse,
                                config,
                                request: {},
                                isAxiosError: false,
                                __isCachedResponse: true
                            };
                        }
                    } catch (error) {
                        // If it's our cached response, re-throw it
                        if (error.__isCachedResponse) {
                            throw error;
                        }
                        console.error('❌ Cache error:', error);
                    }
                }
                
                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        // Response interceptor - convert our fake error into success
        axios.interceptors.response.use(
            (response) => {
                return response;
            },
            (error) => {
                // If this is our fake cached response, return it as success
                if (error.__isCachedResponse && error.response) {
                    console.log('✅ Returning cached response to Inertia');
                    return Promise.resolve(error.response);
                }
                
                return Promise.reject(error);
            }
        );

        // Return a resolved promise so callers can do `.then(...)`
        return Promise.resolve(true);
    }

    /**
     * Get cached page data from IndexedDB
     */
    async getCachedPageData(url) {
        try {
            return await offlineStorage.getPageData(url);
        } catch (error) {
            console.error('Error getting cached page:', error);
            return null;
        }
    }

    /**
     * Destroy the interceptor
     */
    destroy() {
        if (this.interceptorId !== null) {
            axios.interceptors.request.eject(this.interceptorId);
        }
    }
}

const handler = new InertiaOfflineHandlerAxios();
export default handler;
