/**
 * Sincronización de datos offline
 */

// Sincronizar pagos pendientes
async function syncPagosPendientes() {
    if (!navigator.onLine) {
        console.log('Sin conexión, no se puede sincronizar');
        return;
    }
    
    try {
        const pagosPendientes = await getPagosPendientes();
        
        if (pagosPendientes.length === 0) {
            console.log('No hay pagos pendientes de sincronizar');
            return;
        }
        
        console.log(`Sincronizando ${pagosPendientes.length} pagos pendientes...`);
        
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        for (const pago of pagosPendientes) {
            try {
                // Subir foto primero si existe
                let comprobanteUrl = null;
                if (pago.foto_data) {
                    comprobanteUrl = await uploadPhotoToCloudinary(pago.foto_data);
                }
                
                // Preparar datos del pago
                const pagoData = {
                    cuota_id: pago.cuota_id,
                    monto_pagado: pago.monto_pagado,
                    fecha_pago: pago.fecha_pago,
                    metodo_pago: pago.metodo_pago,
                    observaciones: pago.observaciones,
                    comprobante_url: comprobanteUrl,
                    latitud: pago.latitud || null,
                    longitud: pago.longitud || null
                };
                
                // Enviar pago
                const response = await fetch(BASE_URL + '/app/api/pagos/create.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(pagoData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Marcar como sincronizado
                    await markPagoAsSynced(pago.id);
                    console.log(`Pago ${pago.id} sincronizado exitosamente`);
                } else {
                    console.error(`Error sincronizando pago ${pago.id}:`, result.message);
                }
            } catch (error) {
                console.error(`Error procesando pago ${pago.id}:`, error);
            }
        }
        
        // Limpiar pagos sincronizados
        await cleanupSyncedPagos();
        
        console.log('Sincronización completada');
    } catch (error) {
        console.error('Error en sincronización:', error);
    }
}

// Limpiar pagos sincronizados
async function cleanupSyncedPagos() {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pagos_pendientes'], 'readwrite');
        const store = transaction.objectStore('pagos_pendientes');
        const index = store.index('timestamp');
        
        const request = index.getAll();
        request.onsuccess = () => {
            const pagos = request.result;
            pagos.forEach(pago => {
                if (pago.sincronizado) {
                    store.delete(pago.id);
                }
            });
        };
    } catch (error) {
        console.error('Error limpiando pagos sincronizados:', error);
    }
}

// Subir foto a Cloudinary (helper)
async function uploadPhotoToCloudinary(base64Data) {
    const response = await fetch(BASE_URL + '/app/api/upload/cloudinary.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            image: base64Data,
            folder: 'comprobantes-pagos'
        })
    });
    
    const result = await response.json();
    
    if (result.success && result.data.url) {
        return result.data.url;
    } else {
        throw new Error(result.message || 'Error al subir foto');
    }
}

// Helper para obtener cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Sincronizar cuando vuelva la conexión
window.addEventListener('online', function() {
    console.log('Conexión restablecida, iniciando sincronización...');
    syncPagosPendientes();
});

// Sincronizar periódicamente si hay conexión
setInterval(() => {
    if (navigator.onLine) {
        syncPagosPendientes();
    }
}, 60000); // Cada minuto

