/*
 * Bible Reading Tracker service worker.
 *
 * Caching rule that matters most: authenticated HTML is NEVER stored. The first
 * version of this worker cached every navigation keyed by URL alone, so on a
 * shared device the next user could be served the previous user's dashboard or
 * messages from cache. Only the pages in PUBLIC_PAGES — identical for every
 * visitor — are cached; everything else goes to the network, and falls back to
 * a static offline page rather than to someone else's content.
 *
 * Bumping CACHE_VERSION purges every earlier cache in the activate handler,
 * which is also what evicts anything a previous version wrongly stored.
 */
const CACHE_VERSION = "v2";
const CACHE_NAME = `bible-tracker-${CACHE_VERSION}`;
const OFFLINE_URL = "/offline.html";

// Fetched on install. Keep this small: a failure on any one of these aborts the
// whole install and the worker never activates.
const PRECACHE = [
    "/",
    "/login",
    OFFLINE_URL,
    "/manifest.json",
    "/pwa-icons/icon-192x192.png",
];

// Pages safe to cache: no session-specific content.
const PUBLIC_PAGES = new Set([
    "/",
    "/login",
    "/register",
    "/forgot-password",
    "/manifest.json",
]);

// Immutable-ish assets: fingerprinted bundles and icons.
// NOT "/icons/": Apache ships a global `Alias /icons/ "/usr/share/apache2/icons/"`
// in mods-enabled/alias.conf which shadows the app directory, so every icon
// under /icons/ 404s before Laravel ever sees the request.
const ASSET_PREFIXES = ["/build/", "/pwa-icons/"];

const isAsset = (pathname) =>
    ASSET_PREFIXES.some((p) => pathname.startsWith(p)) ||
    pathname === "/favicon.ico" ||
    pathname === "/favicon.svg";

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            // Speeds up the first paint of every navigation this worker handles.
            if (self.registration.navigationPreload) {
                await self.registration.navigationPreload.enable().catch(() => {});
            }

            const keys = await caches.keys();
            await Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)),
            );

            await self.clients.claim();
        })(),
    );
});

// Lets the page trigger an immediate update instead of waiting for all tabs to close.
self.addEventListener("message", (event) => {
    if (event.data === "SKIP_WAITING") self.skipWaiting();
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
        // Refresh in the background so a redeploy is picked up without a stall.
        fetch(request)
            .then((res) => res.ok && cache.put(request, res.clone()))
            .catch(() => {});

        return cached;
    }

    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());

    return response;
}

async function handleNavigation(event) {
    const url = new URL(event.request.url);
    const cacheable = PUBLIC_PAGES.has(url.pathname) && !url.search;

    try {
        // Use the preloaded response when the browser gave us one.
        const response =
            (await event.preloadResponse) || (await fetch(event.request));

        // Only ever store public, non-redirected pages. An authenticated visitor
        // hitting "/" or "/login" is redirected to a private page, and caching
        // that would poison the public shell for the next user.
        if (cacheable && response.ok && !response.redirected) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(event.request, response.clone());
        }

        return response;
    } catch (error) {
        const cache = await caches.open(CACHE_NAME);

        if (cacheable) {
            const cached = await cache.match(event.request);
            if (cached) return cached;
        }

        // Never fall back to another user's page — always the static shell.
        return (await cache.match(OFFLINE_URL)) || Response.error();
    }
}

self.addEventListener("fetch", (event) => {
    const { request } = event;

    if (request.method !== "GET") return;

    const url = new URL(request.url);
    if (url.origin !== location.origin) return;

    if (request.mode === "navigate") {
        event.respondWith(handleNavigation(event));

        return;
    }

    if (isAsset(url.pathname)) {
        event.respondWith(cacheFirst(request).catch(() => Response.error()));

        return;
    }

    // Everything else (authenticated fragments, Livewire, JSON) is left alone.
});
