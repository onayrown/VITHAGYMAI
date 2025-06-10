/**
 * SmartBioFit Service Worker
 * Provides basic offline functionality for the dashboard
 */

const CACHE_NAME = 'smartbiofit-v1.0';
const urlsToCache = [
    '/',
    '/index.php',
    '/assets/css/ios-premium.css',
    '/assets/css/smartbiofit-premium.css',
    '/assets/js/app.js',
    '/assets/images/logo-smartbiofit.png',
    'https://cdn.tailwindcss.com/3.4.0',
    'https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css',
    'https://unpkg.com/aos@2.3.1/dist/aos.css'
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('SmartBioFit: Cache opened');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('SmartBioFit: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
