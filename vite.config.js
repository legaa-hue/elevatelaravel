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
            registerType: 'prompt',
            injectRegister: 'auto',
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            devOptions: {
                enabled: true,
                type: 'module'
            },
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
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                maximumFileSizeToCacheInBytes: 50 * 1024 * 1024, // 50MB
                runtimeCaching: [
                    // API routes - Network first, fallback to cache
                    {
                        urlPattern: /^.*\/api\/.*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache-v1',
                            networkTimeoutSeconds: 10,
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 5 * 60 // 5 minutes
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Teacher dashboard data
                    {
                        urlPattern: /^.*\/teacher\/(dashboard|courses|classwork|calendar).*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'teacher-data-cache-v1',
                            networkTimeoutSeconds: 10,
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
                    // Submission files
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
                    // File attachments (documents, images, etc.)
                    {
                        urlPattern: /^.*\/storage\/.*\.(pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'file-attachments-cache-v2',
                            expiration: {
                                maxEntries: 300,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            },
                            plugins: [
                                {
                                    cacheWillUpdate: async ({ response }) => {
                                        return response.status === 200 ? response : null;
                                    }
                                }
                            ]
                        }
                    },
                    // Images
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache-v1',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 30
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    // Static assets
                    {
                        urlPattern: /\.(?:js|css|woff|woff2|ttf|eot)$/i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'static-assets-cache-v1',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 7 // 7 days
                            }
                        }
                    }
                ]
            }
        })
    ],
});
