/**
 * IndexedDB Helper - Almacenamiento offline
 */

const DB_NAME = 'CobradorDB';
const DB_VERSION = 1;

// Abrir base de datos
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Store para pagos pendientes
            if (!db.objectStoreNames.contains('pagos_pendientes')) {
                const store = db.createObjectStore('pagos_pendientes', {
                    keyPath: 'id',
                    autoIncrement: true
                });
                store.createIndex('timestamp', 'timestamp', { unique: false });
                store.createIndex('cuota_id', 'cuota_id', { unique: false });
            }
            
            // Store para clientes cache
            if (!db.objectStoreNames.contains('clientes_cache')) {
                const store = db.createObjectStore('clientes_cache', {
                    keyPath: 'id'
                });
                store.createIndex('cobrador_id', 'cobrador_id', { unique: false });
            }
            
            // Store para préstamos cache
            if (!db.objectStoreNames.contains('prestamos_cache')) {
                const store = db.createObjectStore('prestamos_cache', {
                    keyPath: 'id'
                });
                store.createIndex('cliente_id', 'cliente_id', { unique: false });
            }
            
            // Store para cuotas cache
            if (!db.objectStoreNames.contains('cuotas_cache')) {
                const store = db.createObjectStore('cuotas_cache', {
                    keyPath: 'id'
                });
                store.createIndex('prestamo_id', 'prestamo_id', { unique: false });
            }
        };
    });
}

// Guardar pago pendiente
async function savePagoToIndexedDB(pagoData) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readwrite');
        const store = transaction.objectStore('pagos_pendientes');
        
        const pago = {
            ...pagoData,
            timestamp: Date.now(),
            sincronizado: false
        };
        
        return new Promise((resolve, reject) => {
            const request = store.add(pago);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error guardando pago en IndexedDB:', error);
        throw error;
    }
}

// Obtener pagos pendientes
async function getPagosPendientes() {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readonly');
        const store = transaction.objectStore('pagos_pendientes');
        const index = store.index('timestamp');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll();
            request.onsuccess = () => resolve(request.result.filter(p => !p.sincronizado));
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error obteniendo pagos pendientes:', error);
        return [];
    }
}

// Eliminar pago pendiente (después de sincronizar)
async function deletePagoPendiente(id) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readwrite');
        const store = transaction.objectStore('pagos_pendientes');
        
        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error eliminando pago pendiente:', error);
        throw error;
    }
}

// Marcar pago como sincronizado
async function markPagoAsSynced(id) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readwrite');
        const store = transaction.objectStore('pagos_pendientes');
        
        return new Promise((resolve, reject) => {
            const getRequest = store.get(id);
            getRequest.onsuccess = () => {
                const pago = getRequest.result;
                if (pago) {
                    pago.sincronizado = true;
                    const putRequest = store.put(pago);
                    putRequest.onsuccess = () => resolve();
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    resolve();
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    } catch (error) {
        console.error('Error marcando pago como sincronizado:', error);
        throw error;
    }
}

// Cachear clientes
async function cacheClientes(clientes) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['clientes_cache'], 'readwrite');
        const store = transaction.objectStore('clientes_cache');
        
        // Limpiar cache anterior
        await store.clear();
        
        // Guardar nuevos
        clientes.forEach(cliente => {
            store.add(cliente);
        });
    } catch (error) {
        console.error('Error cacheando clientes:', error);
    }
}

// Obtener clientes del cache
async function getClientesFromCache(cobradorId) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['clientes_cache'], 'readonly');
        const store = transaction.objectStore('clientes_cache');
        const index = store.index('cobrador_id');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll(cobradorId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error obteniendo clientes del cache:', error);
        return [];
    }
}

// Cachear préstamos
async function cachePrestamos(prestamos) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['prestamos_cache'], 'readwrite');
        const store = transaction.objectStore('prestamos_cache');
        
        prestamos.forEach(prestamo => {
            store.put(prestamo);
        });
    } catch (error) {
        console.error('Error cacheando préstamos:', error);
    }
}

// Obtener préstamos del cache
async function getPrestamosFromCache(clienteId) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['prestamos_cache'], 'readonly');
        const store = transaction.objectStore('prestamos_cache');
        const index = store.index('cliente_id');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll(clienteId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error obteniendo préstamos del cache:', error);
        return [];
    }
}

// Cachear cuotas
async function cacheCuotas(cuotas) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['cuotas_cache'], 'readwrite');
        const store = transaction.objectStore('cuotas_cache');
        
        cuotas.forEach(cuota => {
            store.put(cuota);
        });
    } catch (error) {
        console.error('Error cacheando cuotas:', error);
    }
}

// Obtener cuotas del cache
async function getCuotasFromCache(prestamoId) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['cuotas_cache'], 'readonly');
        const store = transaction.objectStore('cuotas_cache');
        const index = store.index('prestamo_id');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll(prestamoId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Error obteniendo cuotas del cache:', error);
        return [];
    }
}

