/* global clients */
/* Service worker YOWL : pré-cache de l'app shell et notifications push */

// Point d'injection du manifeste de pré-cache (requis par vite-plugin-pwa)
const precacheEntries = self.__WB_MANIFEST || [];
const CACHE_NAME = 'yowl-shell-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            const urls = precacheEntries.map((entry) =>
                typeof entry === 'string' ? entry : entry.url
            );
            return cache.addAll(urls);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

// Stratégie : réseau d'abord, cache en secours (app shell hors ligne)
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);
    // Ne jamais mettre en cache les appels API
    if (url.pathname.startsWith('/api') || url.origin !== self.location.origin) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                return response;
            })
            .catch(() =>
                caches.match(event.request).then(
                    (cached) => cached || caches.match('/index.html')
                )
            )
    );
});

// Réception d'une notification push
self.addEventListener('push', (event) => {
    let data = { title: 'YOWL', body: 'Du nouveau sur YOWL !', url: '/feed' };
    try {
        data = { ...data, ...event.data.json() };
    } catch {
        // Payload non JSON : on garde les valeurs par défaut
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/pwa-192x192.png',
            badge: '/pwa-192x192.png',
            data: { url: data.url },
        })
    );
});

// Clic sur une notification : ouvrir (ou focaliser) la page concernée
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/feed';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});
