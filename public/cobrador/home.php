<?php
/**
 * Home del Cobrador - PWA
 */

require_once __DIR__ . '/../auth_check.php';
requireCobradorOrAdmin();

$user = $GLOBALS['current_user'];
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#3b82f6">
    <title>Cobrador - Sistema Financiero</title>
    <link rel="manifest" href="<?php echo base_url('manifest.json'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sync-indicator {
            position: fixed;
            top: 60px;
            right: 10px;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-indigo-600 text-white shadow-lg sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-user-tie text-2xl"></i>
                    <div>
                        <h1 class="text-lg font-bold">Cobrador</h1>
                        <p class="text-xs opacity-90"><?php echo htmlspecialchars($user['nombre_completo']); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Indicador de conexión -->
                    <div id="connectionStatus" class="px-2 py-1 rounded text-xs">
                        <i class="fas fa-wifi"></i> <span>Online</span>
                    </div>
                    <a href="<?php echo base_url('public/logout.php'); ?>" class="p-2 rounded hover:bg-indigo-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Indicador de sincronización -->
    <div id="syncIndicator" class="sync-indicator hidden">
        <div class="bg-blue-500 text-white px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2">
            <i class="fas fa-sync fa-spin"></i>
            <span>Sincronizando...</span>
        </div>
    </div>

    <!-- Contenido Principal -->
    <main class="p-4 pb-24">
        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Cobros Hoy</p>
                        <p class="text-2xl font-bold text-indigo-600" id="cobrosHoy">0</p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-3">
                        <i class="fas fa-money-bill-wave text-indigo-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Pendientes</p>
                        <p class="text-2xl font-bold text-orange-600" id="pendientesSync">0</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-3">
                        <i class="fas fa-clock text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Acciones Rápidas</h2>
            <div class="grid grid-cols-1 gap-3">
                <a href="<?php echo base_url('public/cobrador/registrar-pago.php'); ?>" 
                   class="btn-primary text-white rounded-lg p-4 shadow-lg flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white bg-opacity-20 rounded-full p-3">
                            <i class="fas fa-camera text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Registrar Pago</h3>
                            <p class="text-sm opacity-90">Tomar foto y registrar cobro</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                
                <a href="<?php echo base_url('public/cobrador/clientes.php'); ?>" 
                   class="bg-white rounded-lg p-4 shadow-lg flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-indigo-100 rounded-full p-3">
                            <i class="fas fa-users text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Mis Clientes</h3>
                            <p class="text-sm text-gray-500">Ver clientes asignados</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                
                <a href="<?php echo base_url('public/cobrador/historial.php'); ?>" 
                   class="bg-white rounded-lg p-4 shadow-lg flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-history text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Historial</h3>
                            <p class="text-sm text-gray-500">Ver cobros realizados</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>

        <!-- Últimos cobros -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Últimos Cobros</h2>
            <div id="ultimosCobros" class="space-y-2">
                <div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40">
        <div class="flex justify-around items-center py-2">
            <a href="<?php echo base_url('public/cobrador/home.php'); ?>" 
               class="flex flex-col items-center px-4 py-2 text-indigo-600">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs font-medium">Inicio</span>
            </a>
            <a href="<?php echo base_url('public/cobrador/registrar-pago.php'); ?>" 
               class="flex flex-col items-center px-4 py-2 text-gray-600">
                <i class="fas fa-camera text-xl mb-1"></i>
                <span class="text-xs">Cobrar</span>
            </a>
            <a href="<?php echo base_url('public/cobrador/clientes.php'); ?>" 
               class="flex flex-col items-center px-4 py-2 text-gray-600">
                <i class="fas fa-users text-xl mb-1"></i>
                <span class="text-xs">Clientes</span>
            </a>
            <a href="<?php echo base_url('public/cobrador/historial.php'); ?>" 
               class="flex flex-col items-center px-4 py-2 text-gray-600">
                <i class="fas fa-history text-xl mb-1"></i>
                <span class="text-xs">Historial</span>
            </a>
        </div>
    </nav>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>';
        const USER_ID = <?php echo $user['id']; ?>;
        
        // Registrar Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo base_url('service-worker.js'); ?>')
                    .then(reg => console.log('Service Worker registrado:', reg))
                    .catch(err => console.error('Error registrando Service Worker:', err));
            });
        }
        
        // Verificar conexión
        function updateConnectionStatus() {
            const statusEl = $('#connectionStatus');
            if (navigator.onLine) {
                statusEl.html('<i class="fas fa-wifi"></i> <span>Online</span>').removeClass('bg-red-500').addClass('bg-green-500');
            } else {
                statusEl.html('<i class="fas fa-wifi-slash"></i> <span>Offline</span>').removeClass('bg-green-500').addClass('bg-red-500');
            }
        }
        
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();
        
        // Cargar estadísticas
        function loadStats() {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/cobrador/stats.php',
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        $('#cobrosHoy').text(response.data.cobros_hoy || 0);
                        loadPendingSync();
                    }
                },
                error: function() {
                    // Si falla, intentar cargar desde IndexedDB
                    loadPendingSync();
                }
            });
            
            loadUltimosCobros();
        }
        
        // Cargar pendientes de sincronización
        function loadPendingSync() {
            if ('indexedDB' in window) {
                const request = indexedDB.open('CobradorDB', 1);
                request.onsuccess = function(event) {
                    const db = event.target.result;
                    if (db.objectStoreNames.contains('pagos_pendientes')) {
                        const transaction = db.transaction(['pagos_pendientes'], 'readonly');
                        const store = transaction.objectStore('pagos_pendientes');
                        const countRequest = store.count();
                        countRequest.onsuccess = function() {
                            $('#pendientesSync').text(countRequest.result || 0);
                        };
                    }
                };
            }
        }
        
        // Cargar últimos cobros
        function loadUltimosCobros() {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/pagos/list.php?limit=5&cobrador_id=' + USER_ID,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success && response.data.pagos.length > 0) {
                        renderUltimosCobros(response.data.pagos);
                    } else {
                        $('#ultimosCobros').html('<div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">No hay cobros recientes</div>');
                    }
                },
                error: function() {
                    $('#ultimosCobros').html('<div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">Error al cargar</div>');
                }
            });
        }
        
        function renderUltimosCobros(cobros) {
            let html = '';
            cobros.forEach(function(cobro) {
                html += `
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-800">${escapeHtml(cobro.cliente_nombre || 'N/A')}</p>
                                <p class="text-sm text-gray-500">${formatDate(cobro.fecha_pago)}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">S/ ${parseFloat(cobro.monto_pagado || 0).toFixed(2)}</p>
                                <span class="px-2 py-1 text-xs rounded ${cobro.estado === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                    ${escapeHtml(cobro.estado || 'pendiente')}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#ultimosCobros').html(html);
        }
        
        // Sincronizar cuando vuelva la conexión
        function syncOfflineData() {
            if (!navigator.onLine) return;
            
            $('#syncIndicator').removeClass('hidden');
            
            if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                navigator.serviceWorker.ready.then(reg => {
                    reg.sync.register('sync-pagos').then(() => {
                        console.log('Sincronización registrada');
                        setTimeout(() => {
                            $('#syncIndicator').addClass('hidden');
                            loadStats();
                        }, 2000);
                    });
                });
            } else {
                // Fallback: sincronizar directamente
                syncPagosPendientes().then(() => {
                    $('#syncIndicator').addClass('hidden');
                    loadStats();
                });
            }
        }
        
        window.addEventListener('online', syncOfflineData);
        
        // Helper functions
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text || '').replace(/[&<>"']/g, m => map[m]);
        }
        
        // Inicializar
        $(document).ready(function() {
            loadStats();
            loadPendingSync();
            
            // Intentar sincronizar cada 30 segundos si hay conexión
            setInterval(() => {
                if (navigator.onLine) {
                    syncOfflineData();
                }
            }, 30000);
        });
    </script>
    <script src="<?php echo base_url('public/cobrador/assets/js/db.js'); ?>"></script>
    <script src="<?php echo base_url('public/cobrador/assets/js/sync.js'); ?>"></script>
</body>
</html>

