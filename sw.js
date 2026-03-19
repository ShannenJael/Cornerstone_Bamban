// Cornerstone Baptist Church Service Worker
// Change this version number when you deploy updates
const CACHE_VERSION = 'v7';
const CACHE_NAME = 'hbc-bamban-' + CACHE_VERSION;

const urlsToCache = [
    '/',
    '/index.html',
    '/css/style.css',
    '/js/main.js',
    '/js/calendar.js',
    '/js/pwa-install.js',
    '/data/calendar-events.json',
    '/images/Cornerstone.jpg',
    '/pages/contact.html',
    '/pages/beliefs.html',
    '/pages/ministries.html',
    '/pages/events.html',
    '/pages/give.html',
    '/pages/visit.html',
    '/pages/heaven.html',
    '/pages/directions.html',
    '/pages/mission.html',
    '/pages/missions.html',
    '/pages/staff.html',
    '/pages/counseling.html',
    '/pages/next-steps.html'
];

// Install event - cache essential files
self.addEventListener('install', function(event) {
    console.log('[SW] Installing version:', CACHE_VERSION);
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('[SW] Caching app shell');
                return cache.addAll(urlsToCache);
            })
            .catch(function(error) {
                console.log('[SW] Cache failed:', error);
            })
    );
    // Don't wait for old service worker to finish - activate immediately
    self.skipWaiting();
});

// Activate event - clean up old caches and notify clients
self.addEventListener('activate', function(event) {
    console.log('[SW] Activating version:', CACHE_VERSION);
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.filter(function(cacheName) {
                    // Delete any cache that doesn't match current version
                    return (cacheName.startsWith('calvary-baptist-') || cacheName.startsWith('hbc-bamban-')) && cacheName !== CACHE_NAME;
                }).map(function(cacheName) {
                    console.log('[SW] Deleting old cache:', cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(function() {
            // Notify all clients that an update happened
            return self.clients.matchAll().then(function(clients) {
                clients.forEach(function(client) {
                    client.postMessage({
                        type: 'SW_UPDATED',
                        version: CACHE_VERSION
                    });
                });
            });
        })
    );
    // Take control of all pages immediately
    self.clients.claim();
});

// Fetch event - network first for HTML, cache first for assets
self.addEventListener('fetch', function(event) {
    var request = event.request;

    // Always fetch dynamic data fresh
    if (request.url.includes('/data/')) {
        event.respondWith(
            fetch(request).catch(function() {
                return caches.match(request);
            })
        );
        return;
    }

    // For HTML pages, try network first (to get latest content)
    if (request.headers.get('Accept').includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then(function(response) {
                    // Cache the fresh HTML
                    var responseClone = response.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(function() {
                    // If offline, serve from cache
                    return caches.match(request).then(function(response) {
                        return response || caches.match('/index.html');
                    });
                })
        );
        return;
    }

    // For other assets (CSS, JS, images), serve from cache first
    event.respondWith(
        caches.match(request)
            .then(function(response) {
                if (response) {
                    return response;
                }

                return fetch(request).then(function(response) {
                    // Don't cache if not a valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone and cache the response
                    var responseToCache = response.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(request, responseToCache);
                    });

                    return response;
                });
            })
            .catch(function() {
                // Return offline fallback for images
                if (request.destination === 'image') {
                    return new Response('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="50%" y="50%" text-anchor="middle">Offline</text></svg>',
                        { headers: { 'Content-Type': 'image/svg+xml' } });
                }
            })
    );
});

// Listen for skip waiting message from client
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});



