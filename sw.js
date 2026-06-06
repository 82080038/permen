/**
 * Service Worker untuk SKD CAT-BKN PWA
 * Cache static assets, materi, soal untuk offline access
 * Background sync untuk jawaban offline
 */

const CACHE_NAME = 'skd-cat-bkn-v6';
const STATIC_ASSETS = [
  '/permen/index.php',
  '/permen/pages/login.php',
  '/permen/pages/register.php',
  '/permen/pages/materi.php',
  '/permen/pages/latihan.php',
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

// Materi and soal content URLs to cache
const CONTENT_CACHE_NAME = 'skd-cat-bkn-content-v1';

// Background sync tag for offline answers
const SYNC_TAG = 'sync-offline-answers';

// Install: cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS.map(url => {
        return new Request(url, { mode: 'no-cors', redirect: 'follow' });
      })).catch(err => {
        console.warn('Service Worker: Some assets failed to cache:', err);
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
        cacheNames.filter(name => name !== CACHE_NAME && name !== CONTENT_CACHE_NAME).map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Register background sync
self.addEventListener('sync', event => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncOfflineAnswers());
  }
});

// Sync offline answers to server
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
        
        if (response.ok) {
          await removeOfflineAnswer(answer.id);
        }
      } catch (e) {
        console.error('Failed to sync answer:', answer.id, e);
      }
    }
    
    // Notify clients that sync is complete
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
      client.postMessage({ type: 'SYNC_COMPLETE' });
    });
  } catch (e) {
    console.error('Sync failed:', e);
  }
}

// Get offline answers from IndexedDB
async function getOfflineAnswers() {
  // This would use IndexedDB in a real implementation
  // For now, return empty array
  return [];
}

// Remove synced answer from IndexedDB
async function removeOfflineAnswer(id) {
  // This would use IndexedDB in a real implementation
}

// Fetch: serve from cache, fallback to network
self.addEventListener('fetch', event => {
  const { request } = event;

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // API calls: network-first with cache fallback
  if (request.url.includes('/api/')) {
    // Special handling for materi and soal APIs - cache for offline
    if (request.url.includes('list_soal.php') || request.url.includes('materi')) {
      event.respondWith(
        fetch(request).then(response => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CONTENT_CACHE_NAME).then(cache => cache.put(request, clone));
          }
          return response;
        }).catch(() => {
          return caches.match(request).then(cached => {
            if (cached) return cached;
            return new Response(JSON.stringify({ error: 'Offline - Tidak dapat mengambil data' }), {
              status: 503,
              headers: { 'Content-Type': 'application/json' }
            });
          });
        })
      );
      return;
    }
    
    // Other API calls: network-first with cache fallback
    event.respondWith(
      fetch(request).then(response => {
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME + '-api').then(cache => cache.put(request, clone));
        }
        return response;
      }).catch(() => {
        return caches.match(request).then(cached => {
          if (cached) return cached;
          return new Response(JSON.stringify({ error: 'Offline - Tidak dapat mengambil data' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
          });
        });
      })
    );
    return;
  }

  // Content assets (materi, soal images): cache-first with network fallback
  if (request.url.includes('/assets/soal/') || request.url.includes('/content/')) {
    event.respondWith(
      caches.match(request).then(cached => {
        if (cached) {
          fetch(request).then(response => {
            if (response.status === 200) {
              const clone = response.clone();
              caches.open(CONTENT_CACHE_NAME).then(cache => cache.put(request, clone));
            }
          });
          return cached;
        }
        return fetch(request).then(response => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CONTENT_CACHE_NAME).then(cache => cache.put(request, clone));
          }
          return response;
        }).catch(() => {
          return new Response('Offline - Content tidak tersedia', { status: 503 });
        });
      })
    );
    return;
  }

  // Static assets: cache-first with network fallback
  event.respondWith(
    caches.match(request).then(cached => {
      if (cached) {
        fetch(request).then(response => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
          }
        });
        return cached;
      }
      return fetch(request).then(response => {
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
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

