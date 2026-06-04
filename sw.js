/**
 * Service Worker untuk SKD CAT-BKN PWA
 * Cache static assets dan halaman utama untuk offline access
 */

const CACHE_NAME = 'skd-cat-bkn-v5';
const STATIC_ASSETS = [
  '/permen/index.php',
  '/permen/pages/login.php',
  '/permen/pages/register.php',
  '/permen/pages/materi.php',
  '/permen/pages/latihan.php',
  // tryout.php is dynamic with session data - don't cache
  // '/permen/pages/tryout.php',
  '/permen/pages/hasil.php',
  '/permen/pages/user_dashboard.php',
  '/permen/pages/admin_dashboard.php',
  '/permen/pages/riwayat_soal.php',
  '/permen/pages/leaderboard.php',
  '/permen/pages/forgot_password.php',
  '/permen/manifest.json',
  '/permen/assets/app.js',
  '/permen/assets/login.css',
  '/permen/assets/form.css',
  '/permen/assets/icon-192.png',
  '/permen/assets/icon-512.png'
];

// Install: cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS.map(url => {
        // Handle individual cache failures with proper redirect handling
        return new Request(url, { mode: 'no-cors', redirect: 'follow' });
      })).catch(err => {
        console.warn('Service Worker: Some assets failed to cache:', err);
        // Continue installation even if some assets fail
        return Promise.resolve();
      });
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

  // API calls: network-first with cache fallback
  if (request.url.includes('/api/')) {
    event.respondWith(
      fetch(request).then(response => {
        // Cache successful API responses
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME + '-api').then(cache => cache.put(request, clone));
        }
        return response;
      }).catch(() => {
        // Try cache if network fails
        return caches.match(request).then(cached => {
          if (cached) return cached;
          // Return offline error for API calls
          return new Response(JSON.stringify({ error: 'Offline - Tidak dapat mengambil data' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
          });
        });
      })
    );
    return;
  }

  // Static assets: cache-first with network fallback
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) {
        // Update cache in background
        fetch(request).then(response => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
          }
        });
        return cached;
      }
      return fetch(request).then(response => {
        // Cache successful responses
        if (response.status === 200) {
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
