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
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            manifest: {
                name: 'ElevateGS Learning Management System',
                short_name: 'ElevateGS',
                description: 'ElevateGS - Progressive Web App LMS for USANT GradSchool. Accessible on all devices.',
                theme_color: '#800000',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                orientation: 'any',
                icons: [
                    {
                        src: 'pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                runtimeCaching: [
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
                    {
                        urlPattern: /^.*\/storage\/submissions\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'submission-files-v1',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /^.*\/storage\/.*\.(pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|ppt|pptx|txt|zip)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'file-attachments-cache',
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
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
                    }
                ]
            }
        })
    ],
});
