const CACHE_NAME = "bible-tracker-v1";
const STATIC_ASSETS = ["/", "/dashboard", "/manifest.json"];

// Install: cache static shell
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)),
    );
    self.skipWaiting();
});

// Activate: remove old caches
self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((k) => k !== CACHE_NAME)
                        .map((k) => caches.delete(k)),
                ),
            ),
    );
    self.clients.claim();
});

// Fetch: network-first for navigation, cache-first for assets
self.addEventListener("fetch", (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET and cross-origin requests
    if (request.method !== "GET" || url.origin !== location.origin) return;

    // Skip Livewire / API / admin requests — always hit the network
    if (
        url.pathname.startsWith("/livewire") ||
        url.pathname.startsWith("/api") ||
        url.pathname.startsWith("/admin")
    )
        return;

    // Cache-first for compiled assets (fingerprinted)
    if (url.pathname.startsWith("/build/")) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((res) => {
                        const clone = res.clone();
                        caches
                            .open(CACHE_NAME)
                            .then((cache) => cache.put(request, clone));
                        return res;
                    }),
            ),
        );
        return;
    }

    // Network-first for HTML pages
    event.respondWith(
        fetch(request)
            .then((res) => {
                const clone = res.clone();
                caches
                    .open(CACHE_NAME)
                    .then((cache) => cache.put(request, clone));
                return res;
            })
            .catch(() => caches.match(request)),
    );
});
