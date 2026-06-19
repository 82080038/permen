/**
 * Service Worker for SKD CAT-BKN Application
 * 
 * Provides offline support for:
 * - Static assets (CSS, JS, images)
 * - Materi pages
 * - Fallback for API requests
 */

const CACHE_NAME = 'skd-cat-bkn-v2';
const STATIC_CACHE = 'skd-static-v2';
const DYNAMIC_CACHE = 'skd-dynamic-v2';

// Static assets to cache on install - use relative paths to work in both environments
// Note: These paths are relative to the service worker location
const STATIC_ASSETS = [
    '../assets/style.css',
    '../assets/login.css',
    '../assets/app.js',
    '../assets/js/api.js',
    '../assets/js/bootstrap.bundle.min.js',
    '../assets/css/bootstrap.min.css',
    '../assets/css/bootstrap-icons.min.css',
];

// Routes that should use network-first strategy
const API_ROUTES = [
    '/api/get_soal.php',
    '/api/submit_jawaban.php',
    '/api/finish_tryout.php',
    '/api/get_notifications.php',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(async (cache) => {
                console.log('[SW] Caching static assets');
                const promises = STATIC_ASSETS.map(async (url) => {
                    try {
                        await cache.add(url);
                    } catch (e) {
                        console.warn('[SW] Failed to cache:', url, e.message);
                    }
                });
                await Promise.all(promises);
                console.log('[SW] Static assets cached (some may have failed)');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            return name.startsWith('skd-') &&
                                name !== STATIC_CACHE &&
                                name !== DYNAMIC_CACHE;
                        })
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Claiming clients');
                return self.clients.claim();
            })
    );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Strategy for static assets
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Strategy for API routes
    if (isApiRoute(url.pathname)) {
        event.respondWith(networkFirstWithCacheFallback(request));
        return;
    }

    // Strategy for HTML pages
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }

    // Default: network first
    event.respondWith(networkFirst(request));
});

/**
 * Check if path is a static asset
 */
function isStaticAsset(pathname) {
    return pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/);
}

/**
 * Check if path is an API route
 */
function isApiRoute(pathname) {
    return API_ROUTES.some(route => pathname.includes(route));
}

/**
 * Cache First strategy - for static assets
 */
async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        // Return cached version and update cache in background
        fetch(request)
            .then((response) => {
                if (response.ok) {
                    cache.put(request, response);
                }
            })
            .catch(() => { }); // Ignore background update errors

        return cached;
    }

    // Not in cache, fetch and cache
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[SW] Cache first failed:', error);
        throw error;
    }
}

/**
 * Network First with Cache Fallback - for API requests
 */
async function networkFirstWithCacheFallback(request) {
    const cache = await caches.open(DYNAMIC_CACHE);

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            // Update cache with fresh data
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[SW] Network failed, trying cache:', request.url);

        const cached = await cache.match(request);

        if (cached) {
            // Return cached with stale indicator
            const headers = new Headers(cached.headers);
            headers.set('X-SW-Cache', 'stale');

            return new Response(cached.body, {
                status: cached.status,
                statusText: cached.statusText,
                headers: headers
            });
        }

        // No cache, return offline error
        return new Response(
            JSON.stringify({
                success: false,
                error: 'Anda sedang offline. Silakan cek koneksi internet Anda.',
                offline: true
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

/**
 * Network First with Offline Fallback - for HTML pages
 */
async function networkFirstWithOfflineFallback(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        console.log('[SW] Page fetch failed, showing offline page');

        // Return offline page
        return new Response(
            `<!DOCTYPE html>
            <html>
            <head>
                <title>Offline - SKD CAT-BKN</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body {
                        font-family: 'Segoe UI', sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        margin: 0;
                        background: #f5f7fa;
                    }
                    .offline-container {
                        text-align: center;
                        padding: 2rem;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        max-width: 400px;
                    }
                    .offline-icon {
                        font-size: 4rem;
                        margin-bottom: 1rem;
                    }
                    h1 {
                        color: #1a5276;
                        margin-bottom: 0.5rem;
                    }
                    p {
                        color: #666;
                        margin-bottom: 1.5rem;
                    }
                    button {
                        background: #2980b9;
                        color: white;
                        border: none;
                        padding: 0.75rem 1.5rem;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 1rem;
                    }
                    button:hover {
                        background: #1a5276;
                    }
                </style>
            </head>
            <body>
                <div class="offline-container">
                    <div class="offline-icon">📡</div>
                    <h1>Anda Offline</h1>
                    <p>Halaman ini memerlukan koneksi internet. Silakan periksa koneksi Anda dan coba lagi.</p>
                    <button onclick="window.location.reload()">Coba Lagi</button>
                </div>
            </body>
            </html>`,
            {
                status: 200,
                headers: { 'Content-Type': 'text/html' }
            }
        );
    }
}

/**
 * Network First strategy - default
 */
async function networkFirst(request) {
    const cache = await caches.open(DYNAMIC_CACHE);

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cached = await cache.match(request);

        if (cached) {
            return cached;
        }

        throw error;
    }
}

// Message handler for skip waiting
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[SW] Service Worker loaded');
