import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    build: {
        // Optimize chunk size and reduce preload warnings
        cssCodeSplit: true,
        modulePreload: {
            polyfill: false, // Disable modulepreload polyfill
        },
        rollupOptions: {
            output: {
                // Better chunk splitting
                manualChunks: undefined,
            }
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false, // We'll register manually
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            devOptions: {
                enabled: false,
                type: 'module'
            },
            // Output sw.js to public root instead of build folder
            filename: 'sw.js',
            strategies: 'generateSW',
            manifest: {
                name: 'ElevateGS Learning Management System',
                short_name: 'ElevateGS',
                description: 'ElevateGS - Progressive Web App LMS for USANT GradSchool. Accessible on all devices with offline support.',
                theme_color: '#7f1d1d',
                background_color: '#ffffff',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                orientation: 'any',
                categories: ['education', 'productivity'],
                lang: 'en-US',
                dir: 'ltr',
                screenshots: [
                    {
                        src: 'screenshot-desktop.png',
                        sizes: '1280x720',
                        type: 'image/png',
                        form_factor: 'wide',
                        label: 'ElevateGS Dashboard - Desktop View'
                    },
                    {
                        src: 'screenshot-mobile.png',
                        sizes: '750x1334',
                        type: 'image/png',
                        form_factor: 'narrow',
                        label: 'ElevateGS Dashboard - Mobile View'
                    }
                ],
                icons: [
                    {
                        src: 'pwa-64x64.png',
                        sizes: '64x64',
                        type: 'image/png'
                    },
                    {
                        src: 'pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: 'pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: 'pwa-maskable-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ],
                shortcuts: [
                    {
                        name: 'Dashboard',
                        short_name: 'Dashboard',
                        description: 'View your dashboard',
                        url: '/dashboard',
                        icons: [{ src: 'pwa-192x192.png', sizes: '192x192' }]
                    },
                    {
                        name: 'Courses',
                        short_name: 'Courses',
                        description: 'View your courses',
                        url: '/student/courses',
                        icons: [{ src: 'pwa-192x192.png', sizes: '192x192' }]
                    }
                ]
            },
            workbox: {
                // ✅ CRITICAL: Cache ALL build assets including dynamic chunks
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                globDirectory: 'public/build',
                // Don't restrict to build folder - cache from entire public directory
                maximumFileSizeToCacheInBytes: 50 * 1024 * 1024, // 50MB
                
                // ✅ Add base path for assets
                modifyURLPrefix: {
                    '': '/build/'
                },
                
                // ✅ CRITICAL: Enable navigation fallback for SPA routing
                navigateFallback: null, // Disable for Laravel - it uses server-side routing
                navigateFallbackDenylist: [/^\/api\//, /^\/storage\//],
                cleanupOutdatedCaches: true,
                
                // ✅ CRITICAL: Skip waiting and claim clients immediately
                skipWaiting: true,
                clientsClaim: true,
                runtimeCaching: [
                    // ✅ CRITICAL: Cache all JavaScript modules (including dynamic imports)
                    {
                        urlPattern: /\/build\/assets\/.*\.js$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'js-modules-cache-v1',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 7 // 7 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // ✅ CRITICAL: Cache all CSS files
                    {
                        urlPattern: /\/build\/assets\/.*\.css$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'css-cache-v1',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 7 // 7 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // ✅ Handle Workbox runtime itself
                    {
                        urlPattern: /workbox-.*\.js$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'workbox-runtime-v1',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            }
                        }
                    },
                    // Navigation requests - Use StaleWhileRevalidate for instant loading
                    {
                        urlPattern: /^\/(teacher|student|admin)\/.*/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'pages-cache-v2',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 // 24 hours
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Catch-all for HTML pages
                    {
                        urlPattern: /.*\.html$/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'html-cache-v1',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 // 24 hours
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // API routes - Network first with short timeout, fallback to cache
                    {
                        urlPattern: /\/api\/.*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache-v2',
                            networkTimeoutSeconds: 5,
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 10 * 60 // 10 minutes
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Google Fonts
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // ✅ AGGRESSIVE: All storage files (auto-download for offline viewing)
                    {
                        urlPattern: /^.*\/storage\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'all-storage-files-v3',
                            expiration: {
                                maxEntries: 500, // Increased for more files
                                maxAgeSeconds: 60 * 60 * 24 * 60 // 60 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            },
                            plugins: [
                                {
                                    cacheWillUpdate: async ({ response }) => {
                                        // Only cache successful responses
                                        return response.status === 200 ? response : null;
                                    }
                                }
                            ]
                        }
                    },
                    // Submission files (legacy - kept for compatibility)
                    {
                        urlPattern: /^.*\/storage\/submissions\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'submission-files-v2',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // File attachments - PDF, Office docs, archives (aggressive caching)
                    {
                        urlPattern: /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z|csv|odt|ods|odp)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'document-files-v3',
                            expiration: {
                                maxEntries: 400,
                                maxAgeSeconds: 60 * 60 * 24 * 90 // 90 days - keep documents longer
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Images (aggressive caching for offline viewing)
                    {
                        urlPattern: /\.(png|jpg|jpeg|svg|gif|webp|ico|bmp|tiff|tif|avif)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache-v2',
                            expiration: {
                                maxEntries: 300, // Increased capacity
                                maxAgeSeconds: 60 * 60 * 24 * 60 // 60 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Static assets (CSS, JS, Fonts)
                    {
                        urlPattern: /\.(js|css|woff|woff2|ttf|eot|otf)$/i,
                        handler: 'CacheFirst', // Changed from StaleWhileRevalidate to CacheFirst for better offline
                        options: {
                            cacheName: 'static-assets-cache-v2',
                            expiration: {
                                maxEntries: 150,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    }
                ]
            }
        })
    ],
});
