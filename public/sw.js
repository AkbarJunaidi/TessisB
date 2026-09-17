/**
 * Service Worker untuk Web Push Notification (TessisB).
 *
 * File ini HARUS ada di /public/sw.js (root domain), bukan di subfolder -
 * scope default service worker terbatas ke folder tempat file ini berada
 * dan semua yang di bawahnya. Didaftarkan lewat navigator.serviceWorker
 * .register('/sw.js') di layouts/app.blade.php.
 *
 * Cuma menangani 2 event: 'push' (pesan masuk dari server, ditampilkan
 * sebagai notifikasi OS) dan 'notificationclick' (user mengklik
 * notifikasi itu, browser dibawa ke halaman Notifikasi).
 */

self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: 'TessisB', body: event.data.text() };
    }

    const title = payload.title || 'TessisB';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/image/logo1.png',
        badge: '/image/logo1.png',
        data: payload.data || {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // Kalau tab aplikasi ini sudah terbuka, fokuskan itu saja
            // (jangan buka tab baru lagi) - lalu arahkan ke halaman Notifikasi.
            for (const client of windowClients) {
                if ('focus' in client) {
                    client.focus();
                    if ('navigate' in client) {
                        client.navigate('/announcements');
                    }
                    return;
                }
            }

            if (clients.openWindow) {
                return clients.openWindow('/announcements');
            }
        })
    );
});
