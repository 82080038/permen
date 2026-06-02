/**
 * Service Worker untuk SKD CAT-BKN PWA
 * Cache static assets dan halaman utama untuk offline access
 */

const CACHE_NAME = 'skd-cat-bkn-v1';
const STATIC_ASSETS = [
  '/permen/index.php',
  '/permen/pages/login.php',
  '/permen/pages/register.php',
  '/permen/pages/materi.php',
  '/permen/pages/latihan.php',
  '/permen/pages/tryout.php',
  '/permen/pages/hasil.php',
  '/permen/pages/user_dashboard.php',
  '/permen/pages/admin_dashboard.php',
  '/permen/manifest.json'
];

// Install: cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate: clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: serve from cache, fallback to network
self.addEventListener('fetch', event => {
  const { request } = event;

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // Skip API calls (don't cache dynamic data)
  if (request.url.includes('/api/')) return;

  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) {
        return cached;
      }
      return fetch(request).then(response => {
        // Cache successful HTML responses
        if (response.status === 200 && response.headers.get('content-type')?.includes('text/html')) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
        }
        return response;
      }).catch(() => {
        // Offline fallback for navigation
        if (request.mode === 'navigate') {
          return caches.match('/permen/index.php');
        }
        return new Response('Offline', { status: 503 });
      });
    })
  );
});
