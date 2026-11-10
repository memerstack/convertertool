// sw.js — Service Worker for ConverterTool.in
// Caches the app shell + tool pages so the site works offline.

const CACHE_VERSION = 'convertertool-v2';
const APP_SHELL = [
    '/',
    '/tools.php',
    '/about.php',
    '/faq.php',
    '/how-it-works.php',
    '/assets/css/style.css',
    '/assets/js/pwa.js',
    '/assets/js/tools/_shared.js',
    '/assets/img/favicon.svg',
    '/manifest.json',
];

// Install: precache the shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => {
            return cache.addAll(APP_SHELL).catch((err) => {
                console.warn('[SW] Some shell files failed to cache:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((k) => k !== CACHE_VERSION)
                    .map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// Fetch: network-first, fallback to cache
// Network-first means updates propagate immediately when online,
// but the app still works offline once cached.
self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Only handle GET
    if (req.method !== 'GET') return;

    // Skip cross-origin CDN requests (pdf-lib, mammoth, etc.)
    // — let the browser handle them with its own cache
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;

    // Skip admin (never cache admin)
    if (url.pathname.startsWith('/admin')) return;

    event.respondWith(
        fetch(req)
            .then((response) => {
                // Cache a copy of successful HTML/CSS/JS/JSON
                if (response.ok && (
                    response.headers.get('content-type') || ''
                ).match(/text\/html|text\/css|application\/javascript|application\/json|image\/svg/)) {
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(req, clone));
                }
                return response;
            })
            .catch(() => {
                // Offline fallback
                return caches.match(req).then((cached) => {
                    if (cached) return cached;
                    // Last resort — serve the tools directory for any HTML request
                    if (req.headers.get('accept')?.includes('text/html')) {
                        return caches.match('/tools.php');
                    }
                    return new Response('Offline', { status: 503 });
                });
            })
    );
});