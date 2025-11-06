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
            injectRegister: false,
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            devOptions: { enabled: false, type: 'module' },
            srcDir: 'resources/js',
            filename: 'sw.js',
            strategies: 'injectManifest',
            injectManifest: { rollupFormat: 'iife' },
            manifest: {
                name: 'ElevateGS Learning Management System',
                short_name: 'ElevateGS',
                description: 'ElevateGS - Progressive Web App LMS with offline support.',
                theme_color: '#7f1d1d',
                background_color: '#ffffff',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                icons: [
                    { src: 'pwa-64x64.png', sizes: '64x64', type: 'image/png' },
                    { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: 'pwa-maskable-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' }
                ]
            }
        })
    ],
});
