/**
 * VithaGymAI Service Worker
 * PWA functionality for offline access
 */

const CACHE_NAME = 'vithagymai-v1.3';
const urlsToCache = [
    // Apenas recursos estáticos - NÃO páginas dinâmicas
    '/assets/js/app.js',
    '/assets/css/ios-premium.css',
    '/assets/css/smartbiofit-premium.css',
    '/assets/css/treino-mobile-style.css',
    '/assets/images/logo-vithagymai.png',
    '/assets/images/logo-smartbiofit.png'
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    console.log('VithaGymAI SW: Installing version', CACHE_NAME);
    // Força a ativação imediata do novo Service Worker
    self.skipWaiting();
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('VithaGymAI: Cache opened');
                // Cache files individually to avoid failures
                return Promise.all(
                    urlsToCache.map(url => {
                        return cache.add(url).catch(err => {
                            console.log('VithaGymAI: Failed to cache:', url, err);
                        });
                    })
                );
            })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', function(event) {
    // CRÍTICO: Ignorar requisições que não são HTTP/HTTPS (ex: de extensões do chrome)
    if (!event.request.url.startsWith('http')) {
        return;
    }

    const url = new URL(event.request.url);

    // CRÍTICO: Ignorar completamente o cache para requisições que não são GET (ex: POST para login)
    if (event.request.method !== 'GET') {
        event.respondWith(fetch(event.request));
        return;
    }
    
    // Lista de páginas que NUNCA devem ser cacheadas
    const dynamicPatterns = ['.php', '/api/', '/login', '/logout', '/dashboard'];

    // Verifica se a URL corresponde a um padrão dinâmico ou é a raiz
    const isDynamic = dynamicPatterns.some(pattern => url.pathname.includes(pattern)) || url.pathname === '/' || url.pathname.endsWith('/index');

    if (isDynamic) {
        // Para páginas dinâmicas: sempre buscar da rede para garantir conteúdo atualizado
        event.respondWith(
            fetch(event.request).catch(() => {
                // Em caso de falha de rede, pode-se retornar uma página de fallback, se houver
                return new Response('Página não disponível offline.', {
                    status: 503,
                    statusText: 'Service Unavailable'
                });
            })
        );
        return;
    }
    
    // Para recursos estáticos (CSS, JS, imagens), usar a estratégia "cache-first"
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Se o recurso estiver no cache, retorna a versão do cache
                if (response) {
                    return response;
                }
                
                // Se não, busca na rede, usa e armazena no cache para uso futuro
                return fetch(event.request).then(function(networkResponse) {
                    // Verificação para garantir que a resposta é válida antes de cachear
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse;
                    }

                    // Clona a resposta. Uma stream só pode ser consumida uma vez.
                    const responseToCache = networkResponse.clone();
                    
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });
                        
                    return networkResponse;
                });
            })
    );
});

// Activate event - cleanup old caches and take control immediately
self.addEventListener('activate', function(event) {
    console.log('VithaGymAI SW: Activating version', CACHE_NAME);
    
    event.waitUntil(
        Promise.all([
            // Limpar caches antigos
            caches.keys().then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName !== CACHE_NAME) {
                            console.log('VithaGymAI: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            // Tomar controle imediato de todas as páginas
            self.clients.claim()
        ])
    );
});

// Mensagem para o cliente quando o SW for atualizado
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
