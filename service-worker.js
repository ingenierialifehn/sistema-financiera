/**
 * Service Worker para PWA - Modo Offline
 * Para la aplicación de cobradores
 */

const CACHE_NAME = 'sistema-financiera-v1';
const OFFLINE_URL = '/public/cobrador/offline.html';

// Archivos estáticos a cachear
const STATIC_ASSETS = [
    '/',
    '/public/cobrador/home.php',
    '/public/cobrador/registrar-pago.php',
    '/public/cobrador/clientes.php',
    '/public/cobrador/historial.php',
    '/manifest.json',
    'https://cdn.tailwindcss.com',
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Instalando...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Cacheando archivos estáticos');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activando...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Eliminando cache antiguo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Interceptar peticiones
self.addEventListener('fetch', (event) => {
    // Solo interceptar peticiones GET
    if (event.request.method !== 'GET') {
        // Para POST/PUT/DELETE, intentar red primero, si falla guardar en IndexedDB
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    // Guardar en IndexedDB para sincronización posterior
                    return storeOfflineRequest(event.request);
                })
        );
        return;
    }
    
    // Para GET, estrategia: Network First, fallback a Cache
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Si la respuesta es válida, actualizar cache
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Si falla la red, buscar en cache
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        
                        // Si es navegación, mostrar página offline
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                        
                        return new Response('Offline', { status: 503 });
                    });
            })
    );
});

// Guardar petición offline en IndexedDB
async function storeOfflineRequest(request) {
    try {
        const db = await openIndexedDB();
        const data = await request.clone().json().catch(() => null);
        
        const offlineRequest = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers.entries()),
            body: data,
            timestamp: Date.now()
        };
        
        await db.put('offline_requests', offlineRequest);
        
        return new Response(JSON.stringify({
            success: true,
            message: 'Guardado para sincronización offline',
            offline: true
        }), {
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (error) {
        console.error('[Service Worker] Error guardando petición offline:', error);
        return new Response(JSON.stringify({
            success: false,
            message: 'Error guardando petición offline'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Abrir IndexedDB
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('SistemaFinancieraDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Store para peticiones offline
            if (!db.objectStoreNames.contains('offline_requests')) {
                const store = db.createObjectStore('offline_requests', {
                    keyPath: 'id',
                    autoIncrement: true
                });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
            
            // Store para datos locales
            if (!db.objectStoreNames.contains('local_data')) {
                db.createObjectStore('local_data', { keyPath: 'key' });
            }
        };
    });
}

// Sincronizar peticiones offline cuando vuelva la conexión
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-offline-requests') {
        event.waitUntil(syncOfflineRequests());
    }
    if (event.tag === 'sync-pagos') {
        event.waitUntil(syncPagosPendientes());
    }
});

// Sincronizar pagos pendientes
async function syncPagosPendientes() {
    try {
        // Abrir IndexedDB
        const db = await openIndexedDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readonly');
        const store = transaction.objectStore('pagos_pendientes');
        const pagos = await new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result.filter(p => !p.sincronizado));
            request.onerror = () => reject(request.error);
        });
        
        if (pagos.length === 0) {
            return;
        }
        
        // Obtener token del cliente
        const clients = await self.clients.matchAll();
        if (clients.length === 0) return;
        
        // Notificar al cliente que sincronice
        clients.forEach(client => {
            client.postMessage({
                type: 'SYNC_PAGOS',
                count: pagos.length
            });
        });
    } catch (error) {
        console.error('[Service Worker] Error sincronizando pagos:', error);
    }
}

async function syncOfflineRequests() {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['offline_requests'], 'readonly');
        const store = transaction.objectStore('offline_requests');
        const requests = await store.getAll();
        
        for (const offlineRequest of requests) {
            try {
                const response = await fetch(offlineRequest.url, {
                    method: offlineRequest.method,
                    headers: offlineRequest.headers,
                    body: JSON.stringify(offlineRequest.body)
                });
                
                if (response.ok) {
                    // Eliminar de IndexedDB si fue exitoso
                    const deleteTransaction = db.transaction(['offline_requests'], 'readwrite');
                    const deleteStore = deleteTransaction.objectStore('offline_requests');
                    await deleteStore.delete(offlineRequest.id);
                }
            } catch (error) {
                console.error('[Service Worker] Error sincronizando petición:', error);
            }
        }
    } catch (error) {
        console.error('[Service Worker] Error en sincronización:', error);
    }
}

