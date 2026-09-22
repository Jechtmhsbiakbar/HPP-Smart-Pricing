const CACHE = 'hpp-smart-pricing-v1';
const STATIC = [
  './',
  './index.php',
  './assets/styles.css?v=5',
  './assets/app.js?v=5',
  './assets/pwa.js?v=1',
  './manifest.webmanifest',
  './assets/HPP_Toko-Bunga-Paubut(200x200).webp',
  './assets/icon-192.svg',
  './assets/icon-512.svg'
];
self.addEventListener('install', event => event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(STATIC)).then(() => self.skipWaiting())));
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  const isStatic = url.origin === self.location.origin &&
    (url.pathname.includes('/assets/') ||
      url.pathname.endsWith('/manifest.webmanifest') ||
      url.pathname.endsWith('/service-worker.js'));
  if (!isStatic) return;
  event.respondWith(fetch(event.request).then(response => {
    const copy = response.clone();
    caches.open(CACHE).then(cache => cache.put(event.request, copy));
    return response;
  }).catch(() => caches.match(event.request)));
});
