const CACHE_NAME = 'tdc-workshop-cache-v1';
const ASSETS = [
    '/offline',
    '/images/generic-icon.png'
];

// Install Event
self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event
self.addEventListener('fetch', (e) => {
    // Only handle GET requests
    if (e.request.method !== 'GET') {
        return;
    }

    // Skip Chrome Extensions or non-http requests
    if (!e.request.url.startsWith(self.location.origin)) {
        return;
    }

    e.respondWith(
        caches.match(e.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(e.request).then((networkResponse) => {
                // If response is valid, return it
                if (networkResponse && networkResponse.status === 200) {
                    return networkResponse;
                }
                return networkResponse;
            }).catch(() => {
                // If offline and requesting page, return offline page
                if (e.request.headers.get('accept') && e.request.headers.get('accept').includes('text/html')) {
                    return caches.match('/offline');
                }
            });
        })
    );
});
