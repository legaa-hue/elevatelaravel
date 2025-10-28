# Laravel Vue Inertia PWA Setup Guide

## Project Overview
This document describes the complete setup of a Laravel application with Vue.js frontend, Inertia.js for SPA behavior, and Progressive Web App (PWA) capabilities.

**Project Location:** `c:\Users\Florian Monte\Music\ElevateGS_LaravelPWA`

---

## Initial Setup

### 1. Laravel Installation
```powershell
composer create-project laravel/laravel .
```

**Installed:** Laravel 12.35.1 with SQLite database

### 2. Laravel Breeze Installation
```powershell
composer require laravel/breeze --dev
php artisan breeze:install vue --dark
```

**Packages Installed:**
- `inertiajs/inertia-laravel` ^2.0
- `laravel/sanctum` ^4.0
- `tightenco/ziggy` ^2.0
- `laravel/breeze` ^2.3

### 3. Composer Configuration
Due to network timeout issues, increased timeout:
```powershell
composer config --global process-timeout 2000
```

---

## Frontend Setup

### 1. Node Dependencies

**Initial package.json issues:**
- Version conflict between Vite 7 and @vitejs/plugin-vue requiring Vite 5-6
- Resolved by using compatible versions

**Final package.json:**
```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "@inertiajs/vue3": "^2.0.0",
        "@tailwindcss/forms": "^0.5.3",
        "@tailwindcss/vite": "^4.0.0",
        "@vitejs/plugin-vue": "^5.0.0",
        "autoprefixer": "^10.4.12",
        "axios": "^1.11.0",
        "laravel-vite-plugin": "^1.0.5",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.2.1",
        "vite": "^5.0.0",
        "vue": "^3.4.0"
    }
}
```

### 2. PWA Plugin Installation
```powershell
npm install --save-dev vite-plugin-pwa workbox-window
```

---

## Configuration Files

### 1. Vite Configuration (`vite.config.js`)

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
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
                name: 'Laravel Vue PWA',
                short_name: 'LaravelPWA',
                description: 'Laravel application with Vue and Inertia.js as PWA',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
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
                    }
                ]
            }
        })
    ],
});
```

### 2. Inertia Middleware (`app/Http/Middleware/HandleInertiaRequests.php`)

**Created by Breeze** - Handles shared props including:
- User authentication state
- Ziggy routes for client-side routing

### 3. Bootstrap Configuration (`bootstrap/app.php`)

**Breeze automatically configured** middleware:
```php
$middleware->web(append: [
    \App\Http\Middleware\HandleInertiaRequests::class,
]);
```

### 4. App Entry Point (`resources/js/app.js`)

```javascript
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

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

// Register PWA Service Worker
import { registerSW } from 'virtual:pwa-register';

registerSW({
    onNeedRefresh() {
        console.log('New content available, please refresh.');
    },
    onOfflineReady() {
        console.log('App ready to work offline');
    },
});
```

### 5. Root Template (`resources/views/app.blade.php`)

**Created by Breeze** - Contains:
- Inertia root element `@inertia`
- Vite assets `@vite`
- Ziggy routes `@routes`
- PWA manifest links

---

## Custom Landing Page

### File: `resources/js/Pages/Landing.vue`

**Features:**
- Modern gradient design (indigo → purple → pink)
- PWA install button with auto-detection
- Installation status indicators
- Offline-ready notifications
- Feature showcase grid
- Tech stack badges
- Responsive layout
- Smooth animations

**PWA Integration Logic:**
- Detects `beforeinstallprompt` event
- Shows install button when available
- Handles PWA installation
- Detects standalone mode (installed app)
- Shows offline-ready status when service worker is active

### Route Configuration (`routes/web.php`)

```php
Route::get('/', function () {
    return Inertia::render('Landing');
});
```

---

## Build Process

### Development Build
```powershell
npm run build
```

**Generated Files:**
- `public/build/manifest.json` - Asset manifest
- `public/build/manifest.webmanifest` - PWA manifest
- `public/build/sw.js` - Service worker
- `public/build/workbox-*.js` - Workbox runtime
- All compiled Vue components and CSS

**Build Output:**
```
PWA v1.1.0
mode      generateSW
precache  22 entries (337.91 KiB)
files generated
  public/build/sw.js
  public/build/workbox-b833909e.js
```

---

## Running the Application

### Two Terminal Setup Required:

**Terminal 1 - Laravel Server:**
```powershell
cd "c:\Users\Florian Monte\Music\ElevateGS_LaravelPWA"
php artisan serve
```
Runs on: `http://127.0.0.1:8000`

**Terminal 2 - Vite Dev Server:**
```powershell
cd "c:\Users\Florian Monte\Music\ElevateGS_LaravelPWA"
npm run dev
```
Runs on: `http://localhost:5173`

**Access Application:** `http://127.0.0.1:8000`

---

## Architecture Overview

### Technology Stack
- **Backend:** Laravel 12.35.1
- **Frontend:** Vue 3.4
- **SPA Framework:** Inertia.js 2.0
- **Build Tool:** Vite 5.4
- **Styling:** Tailwind CSS 3.2 + Tailwind CSS Vite 4.0
- **PWA:** Vite PWA Plugin 1.1.0 + Workbox
- **Routing:** Ziggy 2.6.0
- **Authentication:** Laravel Breeze 2.3

### How It Works

**1. SPA Behavior (Inertia.js):**
- Server-side routing via Laravel
- Client-side rendering via Vue
- No need for separate API
- Automatic page transitions without full page reloads

**2. PWA Capabilities:**
- Service worker caches assets automatically
- Works offline after first visit
- Can be installed as native app
- Push notification ready (not implemented yet)

**3. Development Workflow:**
- Vite provides Hot Module Replacement (HMR)
- Changes reflect instantly without page refresh
- Laravel handles backend logic and routing
- Vue components are pre-compiled and optimized

---

## Available Pages

### Public Routes:
- `/` - Landing page (Landing.vue)
- `/login` - Login page
- `/register` - Registration page
- `/forgot-password` - Password reset request
- `/reset-password` - Password reset form

### Authenticated Routes:
- `/dashboard` - User dashboard
- `/profile` - User profile management

---

## Key Features Implemented

✅ **Single Page Application** - Smooth navigation without page reloads
✅ **Progressive Web App** - Installable, offline-capable
✅ **Authentication System** - Complete auth flow with Breeze
✅ **Modern UI** - Tailwind CSS with custom landing page
✅ **Hot Module Replacement** - Instant dev updates
✅ **Service Worker** - Automatic asset caching
✅ **Responsive Design** - Mobile-first approach
✅ **Type-safe Routing** - Ziggy for client-side route generation

---

## Notes for Future Development

### PWA Icons Needed:
Create and place these files in `public/`:
- `pwa-192x192.png` (192x192 pixels)
- `pwa-512x512.png` (512x512 pixels)
- `apple-touch-icon.png` (180x180 pixels)
- `favicon.ico`

### Potential Enhancements:
1. Add push notification support
2. Implement offline data sync
3. Add more caching strategies
4. Create app update notification UI
5. Add SSR support (server-side rendering)
6. Implement background sync

### Known Issues:
- npm deprecation warnings (non-breaking, cosmetic only)
- PHP_CLI_SERVER_WORKERS message (informational, not an error)

---

## Troubleshooting

### Network Timeout Issues:
If experiencing Composer timeouts:
```powershell
composer config --global process-timeout 2000
```

### npm Peer Dependency Conflicts:
Use legacy peer deps if needed:
```powershell
npm install --legacy-peer-deps
```

### Service Worker Not Updating:
Clear browser cache or use incognito mode for testing

### Assets Not Loading:
Ensure both Laravel and Vite servers are running simultaneously

---

## Summary

This setup provides a complete modern web application with:
- Traditional server-side Laravel backend
- Reactive Vue.js frontend
- SPA navigation via Inertia.js
- Progressive Web App capabilities
- Offline support
- Native app installation
- Hot module replacement for development
- Production-ready build pipeline

All working together seamlessly without needing a separate API layer or complex state management!
