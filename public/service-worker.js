const CACHE_NAME = 'antarasa-shell-v1';
const SHELL_ASSETS = [
    '/css/antarasa.css',
    '/js/antarasa.js',
    '/images/antarasa-logo.jpg',
    '/images/antarasa-apple-touch-icon.png',
    '/images/antarasa-icon-192.png',
    '/images/antarasa-icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(SHELL_ASSETS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith('antarasa-shell-') && cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const requestUrl = new URL(event.request.url);

    if (
        event.request.method !== 'GET'
        || requestUrl.origin !== self.location.origin
        || !SHELL_ASSETS.includes(requestUrl.pathname)
    ) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => cachedResponse || fetch(event.request)),
    );
});
