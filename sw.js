// Service Worker para Senior Floors CRM
// Permite instalação como PWA; NÃO cacheia system.php para evitar tela em branco ao clicar em CRM

const CACHE_NAME = 'senior-floors-crm-v2';
const urlsToCache = [
  '/assets/logoSeniorFloors.png',
  '/manifest.json',
  '/styles.css'
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch((error) => {
        console.error('Cache install failed:', error);
      })
  );
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);
  // Nunca cachear system.php: sempre buscar da rede para CRM/Dashboard etc. terem dados atualizados
  if (url.pathname.endsWith('system.php')) {
    event.respondWith(fetch(event.request));
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) {
          return response;
        }
        return fetch(event.request).then((response) => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
          return response;
        }).catch(() => {
          if (event.request.destination === 'document') {
            return new Response(
              '<!DOCTYPE html><html><body><p>Sem conexão. Verifique a internet e tente novamente.</p></body></html>',
              { status: 503, statusText: 'Offline', headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
          }
          return Promise.reject();
        });
      })
  );
});

// Notificação de atualização disponível
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
