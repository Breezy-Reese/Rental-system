
/*
 * PropertyPro PWA service worker.
 *
 * This version deliberately does not cache pages or responses.
 * Login, admin/customer pages, API requests and payments remain
 * network-only to avoid exposing private or stale information.
 */

self.addEventListener("install", function (event) {
    self.skipWaiting();
});

self.addEventListener("activate", function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", function (event) {
    const request = event.request;

    if (request.method !== "GET") {
        return;
    }

    const requestUrl = new URL(request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    // Never cache PHP pages, authentication, API or payment requests.
    if (
        requestUrl.pathname.endsWith(".php") ||
        requestUrl.pathname.startsWith("/api/") ||
        requestUrl.pathname.startsWith("/admin/") ||
        requestUrl.pathname.startsWith("/customer/") ||
        requestUrl.pathname.toLowerCase().includes("mpesa") ||
        requestUrl.pathname.toLowerCase().includes("payment") ||
        requestUrl.pathname.toLowerCase().includes("logout")
    ) {
        return;
    }

    // No cache is used in this version. The browser requests the
    // latest resource from the network.
    event.respondWith(fetch(request));
});
