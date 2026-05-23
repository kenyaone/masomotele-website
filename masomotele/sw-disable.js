// Masomotele Portal Service Worker
// Overrides WordPress SW — no caching, all requests pass through fresh
const CACHE_NAME = 'masomotele-v1';

self.addEventListener('install', function(e) {
  // Take over immediately without waiting
  self.skipWaiting();
});

self.addEventListener('activate', function(e) {
  e.waitUntil(
    // Delete ALL caches so old WP cached files are gone
    caches.keys().then(function(keys) {
      return Promise.all(keys.map(function(k) {
        console.log('Masomotele SW: clearing cache', k);
        return caches.delete(k);
      }));
    }).then(function() {
      // Claim all clients immediately
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function(e) {
  // Never cache — always fetch fresh from network
  // This stops the WP SW from intercepting and breaking POST requests
  if (e.request.method !== 'GET') {
    // For POST requests (toggle saves, suggestions), go straight to network
    return;
  }
  // For GET requests, fetch fresh — no cache
  e.respondWith(
    fetch(e.request).catch(function() {
      // Offline fallback — return cached if available
      return caches.match(e.request);
    })
  );
});
