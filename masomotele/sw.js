// M.T.T.I Learn — Service Worker (PWA offline support)
const CACHE = "mtti-learn-v3";
const OFFLINE_URL = "/mtti-lms/offline.html";

self.addEventListener("install", e => {
  self.skipWaiting();
});

self.addEventListener("activate", e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", e => {
  // Only handle GET requests
  if (e.request.method !== "GET") return;
  // Skip chrome-extension and non-http
  if (!e.request.url.startsWith("http")) return;
  
  e.respondWith(
    fetch(e.request)
      .then(response => {
        // Cache successful responses
        if (response && response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone)).catch(() => {});
        }
        return response;
      })
      .catch(() => {
        // Try cache on network failure
        return caches.match(e.request).then(cached => {
          if (cached) return cached;
          // Return offline page for navigation requests
          if (e.request.mode === "navigate") {
            return caches.match(OFFLINE_URL).then(r => r || new Response("Offline", {status: 503}));
          }
          return new Response("Offline", {status: 503});
        });
      })
  );
});
