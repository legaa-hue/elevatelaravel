/* Custom Workbox Service Worker (injectManifest) */
/* eslint-disable no-undef */
import { clientsClaim } from 'workbox-core';
import { precacheAndRoute, createHandlerBoundToURL, matchPrecache } from 'workbox-precaching';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { CacheFirst, StaleWhileRevalidate, NetworkFirst, NetworkOnly } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BackgroundSyncPlugin } from 'workbox-background-sync';

self.skipWaiting();
clientsClaim();

// Precache build assets (injected at build time)
// Adjust URLs to include '/build/' prefix for Vite/Laravel production paths
const __WB_MANIFEST_ADJUSTED = (self.__WB_MANIFEST || []).map((entry) => {
		try {
				const url = entry && entry.url ? String(entry.url) : '';
				// If absolute or starts with '/', leave as-is
				if (!url || url.startsWith('/') || /^https?:\/\//i.test(url)) return entry;
				// Prefix Vite assets with '/build/' for correct production path
				return { ...entry, url: `/build/${url}` };
		} catch { return entry; }
});
// Offline-first SPA shell routes to precache (add more as needed)
// NOTE: Include only safe/public routes or routes whose HTML can serve as a generic shell.
// Avoid precaching HTML routes that may redirect/auth-guard and break install
const SPA_SHELL_ROUTES = [];

const ADDITIONAL_MANIFEST_ENTRIES = [
	// Offline shell HTML
	{ url: '/offline.html', revision: null },
	// Laravel-served SPA entry points
	...SPA_SHELL_ROUTES.map((url) => ({ url, revision: null })),
];

const PRECACHE_ENTRIES = [
	...__WB_MANIFEST_ADJUSTED,
	...ADDITIONAL_MANIFEST_ENTRIES,
];

precacheAndRoute(PRECACHE_ENTRIES);

// Build an HTML app shell at runtime using cached Vite manifest
async function buildAppShellHtml() {
  try {
    const manifestResp = await caches.match('/build/manifest.json');
    if (!manifestResp) return null;
    const manifest = await manifestResp.json();
    // Find app entry (heuristics: isEntry or filename contains '/app-')
    let appEntry = null;
    for (const [k, v] of Object.entries(manifest)) {
      if (v && (v.isEntry || /\/app-/.test(String(v.file || '')))) { appEntry = v; break; }
    }
    if (!appEntry || !appEntry.file) return null;
    const appJs = appEntry.file.startsWith('/build/') ? appEntry.file : `/build/${appEntry.file}`;
    const cssLinks = (Array.isArray(appEntry.css) ? appEntry.css : [])
      .map(h => h.startsWith('/build/') ? h : `/build/${h}`)
      .map(h => `<link rel="stylesheet" href="${h}">`).join('');
    const html = `<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">${cssLinks}</head><body><div id="app"></div><script type="module" src="${appJs}"></script></body></html>`;
    return new Response(html, { headers: { 'Content-Type': 'text/html; charset=UTF-8' } });
  } catch { return null; }
}

// SPA navigation: try exact precached page first, then runtime app shell, then offline.html
const navigationHandler = async ({ request }) => {
	try {
		// Try to serve an exact precached HTML for this path
		const url = new URL(request.url);
		const exact = await matchPrecache(url.pathname);
		if (exact) {
			// debug notify
			try { self.clients && self.clients.matchAll({includeUncontrolled:true,type:'window'}).then(cs=>cs.forEach(c=>c.postMessage({type:'SW_NAV', path:url.pathname, hit:'exact'}))); } catch {}
			return exact;
		}
	} catch {}

		// Try runtime-generated app shell (uses cached manifest and prewarmed assets)
		const shell = await buildAppShellHtml();
		if (shell) {
			try { self.clients && self.clients.matchAll({includeUncontrolled:true,type:'window'}).then(cs=>cs.forEach(c=>c.postMessage({type:'SW_NAV', path:new URL(request.url).pathname, hit:'app-shell'}))); } catch {}
			return shell;
		}
	const offline = await matchPrecache('/offline.html');
	if (offline) {
		try { self.clients && self.clients.matchAll({includeUncontrolled:true,type:'window'}).then(cs=>cs.forEach(c=>c.postMessage({type:'SW_NAV', path:new URL(request.url).pathname, hit:'offline'}))); } catch {}
		return offline;
	}
	return createHandlerBoundToURL('/offline.html')({ request });
};
const navigationDenylist = [/^\/api\//, /^\/storage\//];
registerRoute(new NavigationRoute(navigationHandler, { denylist: navigationDenylist }));

// Background Sync for non-GET API requests (NetworkOnly + bgSync)
const bgSync = new BackgroundSyncPlugin('offlineCrudQueue', { maxRetentionTime: 24 * 60 });
const nonGetApi = ({ url, request }) => url.pathname.startsWith('/api/') && request.method !== 'GET';
registerRoute(nonGetApi, new NetworkOnly({ plugins: [bgSync] }), 'POST');
registerRoute(nonGetApi, new NetworkOnly({ plugins: [bgSync] }), 'PUT');
registerRoute(nonGetApi, new NetworkOnly({ plugins: [bgSync] }), 'PATCH');
registerRoute(nonGetApi, new NetworkOnly({ plugins: [bgSync] }), 'DELETE');

// API GET: NetworkFirst with cache fallback
registerRoute(/\/api\/.*$/i, new NetworkFirst({
	cacheName: 'api-cache-v2',
	networkTimeoutSeconds: 5,
	plugins: [
		new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 10 * 60 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}), 'GET');

// Build JS/CSS assets
registerRoute(/\/build\/assets\/.*\.js$/i, new CacheFirst({
	cacheName: 'js-modules-cache-v1',
	plugins: [
		new ExpirationPlugin({ maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 7 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));
registerRoute(/\/build\/assets\/.*\.css$/i, new CacheFirst({
	cacheName: 'css-cache-v1',
	plugins: [
		new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 7 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));

// Workbox runtime
registerRoute(/workbox-.*\.js$/i, new CacheFirst({
	cacheName: 'workbox-runtime-v1',
	plugins: [ new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 30 }) ]
}));

// Pages and HTML
registerRoute(/^\/(teacher|student|admin)\/.*$/i, new StaleWhileRevalidate({
	cacheName: 'pages-cache-v2',
	plugins: [
		new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));
registerRoute(/.*\.html$/i, new StaleWhileRevalidate({
	cacheName: 'html-cache-v1',
	plugins: [
		new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));

// Storage and documents
registerRoute(/^.*\/storage\/.*$/i, new CacheFirst({
	cacheName: 'all-storage-files-v3',
	plugins: [
		new ExpirationPlugin({ maxEntries: 500, maxAgeSeconds: 60 * 60 * 24 * 60 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));
registerRoute(/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|7z|csv|odt|ods|odp)$/i, new CacheFirst({
	cacheName: 'document-files-v3',
	plugins: [
		new ExpirationPlugin({ maxEntries: 400, maxAgeSeconds: 60 * 60 * 24 * 90 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));

// Images and static assets
registerRoute(/\.(png|jpg|jpeg|svg|gif|webp|ico|bmp|tiff|tif|avif)$/i, new CacheFirst({
	cacheName: 'images-cache-v2',
	plugins: [
		new ExpirationPlugin({ maxEntries: 300, maxAgeSeconds: 60 * 60 * 24 * 60 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));
registerRoute(/\.(js|css|woff|woff2|ttf|eot|otf)$/i, new CacheFirst({
	cacheName: 'static-assets-cache-v2',
	plugins: [
		new ExpirationPlugin({ maxEntries: 150, maxAgeSeconds: 60 * 60 * 24 * 30 }),
		new CacheableResponsePlugin({ statuses: [0, 200] })
	]
}));

// --------------------------------------
// Prewarm JS/CSS from Vite manifest
// --------------------------------------
async function prewarmFromViteManifest(){
  try {
    const res = await fetch('/build/manifest.json', { cache: 'no-store' });
    if (!res.ok) return;
    const manifest = await res.json();
    const seen = new Set();
    const urls = [];
    const add = (p) => { if (p && !seen.has(p)) { seen.add(p); urls.push(p); } };
    const toAbs = (p) => p.startsWith('/build/') ? p : `/build/${p.replace(/^\/?/, '')}`;
    const visit = (entry) => {
      if (!entry) return;
      if (entry.file) add(toAbs(entry.file));
      if (Array.isArray(entry.css)) entry.css.forEach(c => add(toAbs(c)));
      if (Array.isArray(entry.assets)) entry.assets.forEach(a => add(toAbs(a)));
      if (Array.isArray(entry.imports)) entry.imports.forEach(k => visit(manifest[k]));
    };
    Object.values(manifest).forEach(visit);

    // Split by type and add to corresponding runtime caches
    const jsUrls = urls.filter(u => /\.js(\?.*)?$/i.test(u));
    const cssUrls = urls.filter(u => /\.css(\?.*)?$/i.test(u));

    const jsCache = await caches.open('js-modules-cache-v1');
    const cssCache = await caches.open('css-cache-v1');
    try { await jsCache.addAll(jsUrls); } catch {}
    try { await cssCache.addAll(cssUrls); } catch {}
  } catch {}
}

self.addEventListener('install', (event) => {
  event.waitUntil(prewarmFromViteManifest());
});
