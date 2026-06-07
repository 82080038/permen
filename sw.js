/**
 * Service Worker untuk SKD CAT-BKN PWA
 * Cache static assets, materi, soal untuk offline access
 * Background sync untuk jawaban offline
 */

const CACHE_VERSION = 'v7';
const CACHE_NAME = 'skd-cat-bkn-' + CACHE_VERSION;
const CONTENT_CACHE_NAME = 'skd-cat-bkn-content-' + CACHE_VERSION;

// Only cache public pages — NEVER cache auth-protected pages
const STATIC_ASSETS = [
  '/permen/index.php',
  '/permen/pages/login.php',
  '/permen/pages/register.php',
  '/permen/pages/materi.php',
  '/permen/pages/leaderboard.php',
  '/permen/pages/forgot_password.php',
  '/permen/manifest.json',
  '/permen/assets/app.js',
  '/permen/assets/login.css',
  '/permen/assets/form.css',
  '/permen/assets/icon-192.png',
  '/permen/assets/icon-512.png'
];

// Pages that require authentication — bypass service worker completely
const AUTH_PAGES = [
  '/permen/pages/user_dashboard.php',
  '/permen/pages/admin_dashboard.php',
  '/permen/pages/latihan.php',
  '/permen/pages/tryout.php',
  '/permen/pages/hasil.php',
  '/permen/pages/riwayat_soal.php'
];

const SYNC_TAG = 'sync-offline-answers';

function isAuthPage(url) {
  const path = new URL(url).pathname;
  return AUTH_PAGES.some(p => path === p || path.startsWith(p + '?'));
}

// Install: cache only public static assets
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('SW: Some assets failed to cache:', err);
      });
    })
  );
});

// Activate: delete ALL old caches regardless of name
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(names => {
      return Promise.all(
        names.map(name => {
          if (!name.includes(CACHE_VERSION)) {
            return caches.delete(name);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Background sync
self.addEventListener('sync', event => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncOfflineAnswers());
  }
});

async function syncOfflineAnswers() {
  try {
    const offlineAnswers = await getOfflineAnswers();
    for (const answer of offlineAnswers) {
      try {
        const response = await fetch('/permen/api/save_answer.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(answer)
        });
        if (response.ok) await removeOfflineAnswer(answer.id);
      } catch (e) {
        console.error('Failed to sync answer:', answer.id, e);
      }
    }
    const clients = await self.clients.matchAll();
    clients.forEach(client => client.postMessage({ type: 'SYNC_COMPLETE' }));
  } catch (e) {
    console.error('Sync failed:', e);
  }
}

async function getOfflineAnswers() { return []; }
async function removeOfflineAnswer(id) { }

// Helper: strip redirect flag from response
function cleanResponse(response) {
  if (!response.redirected) return response;
  // For non-navigation, return as-is
  return response;
}

// Fetch handler
self.addEventListener('fetch', event => {
  const { request } = event;

  // Skip non-GET
  if (request.method !== 'GET') return;

  // Bypass SW completely for auth pages (let browser handle redirects)
  if (isAuthPage(request.url)) {
    return;
  }

  // API calls
  if (request.url.includes('/api/')) {
    event.respondWith(
      fetch(request).then(response => {
        if (response.ok && !response.redirected) {
          const clone = response.clone();
          const cacheName = request.url.includes('list_soal.php') || request.url.includes('materi')
            ? CONTENT_CACHE_NAME : (CACHE_NAME + '-api');
          caches.open(cacheName).then(cache => cache.put(request, clone));
        }
        return response;
      }).catch(() => {
        return caches.match(request).then(cached => {
          if (cached) return cached;
          return new Response(JSON.stringify({ error: 'Offline' }), {
            status: 503, headers: { 'Content-Type': 'application/json' }
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
        // Background refresh
        fetch(request).then(response => {
          if (response.ok && !response.redirected) {
            caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
          }
        }).catch(() => { });
        return cached;
      }
      return fetch(request).then(response => {
        if (response.ok && !response.redirected) {
          caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
        }
        return response;
      }).catch(() => {
        if (request.mode === 'navigate') {
          return caches.match('/permen/index.php');
        }
        return new Response('Offline', { status: 503 });
      });
    })
  );
});

