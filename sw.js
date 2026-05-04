const CACHE_NAME = 'LexChat-v2';
const urlsToCache = [
  '/',
  '/index.php',
  '/admin.php',
  '/api.php',
  '/broadcast_api.php',
  '/connect.php',
  '/style.css',
  '/script.js',
  '/icon.png',
  '/manifest.json',
  '/fonDefault.png'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(keyList) {
      return Promise.all(keyList.map(function(key) {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function(event) {
    // Пропускаем POST запросы (их нельзя кэшировать)
    if (event.request.method === 'POST') {
        event.respondWith(fetch(event.request));
        return;
    }
    
    if (event.request.url.includes('api.php') || 
        event.request.url.includes('broadcast_api.php') ||
        event.request.url.includes('admin.php?action=') ||
        event.request.url.includes('_t=') ||
        event.request.url.includes('t=')) {
        event.respondWith(fetch(event.request));
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request).then(function(response) {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    var responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });
                    return response;
                });
            })
            .catch(function() {
                if (event.request.mode === 'navigate') {
                    return caches.match('/index.php');
                }
                return new Response('Network error', {
                    status: 408,
                    statusText: 'No internet connection'
                });
            })
    );
});

self.addEventListener('push', function(event) {
  var options = {
    body: event.data ? event.data.text() : 'Новое сообщение',
    icon: '/icon.png',
    badge: '/icon.png',
    vibrate: [200, 100, 200],
    requireInteraction: true
  };
  event.waitUntil(
    self.registration.showNotification('SimpleChat', options)
  );
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(function(clientList) {
        if (clientList.length > 0) {
          return clientList[0].focus();
        }
        return clients.openWindow('/');
      })
  );
});