// Service Worker para Senior Floors CRM
// Permite instalação como PWA e funcionamento offline básico

const CACHE_NAME = 'senior-floors-crm-v1';
const urlsToCache = [
  '/',
  '/system.php',
  '/system.php?module=dashboard',
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
  // Apenas cachear requisições GET
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Retornar do cache se disponível
        if (response) {
          return response;
        }

        // Buscar da rede
        return fetch(event.request).then((response) => {
          // Verificar se a resposta é válida
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clonar a resposta
          const responseToCache = response.clone();

          // Adicionar ao cache
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });

          return response;
        }).catch(() => {
          // Se falhar, retornar página offline básica
          if (event.request.destination === 'document') {
            return caches.match('/system.php?module=dashboard');
          }
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
