<?php
/**
 * Historial de Cobros - Cobrador
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
    <meta name="theme-color" content="#3b82f6">
    <title>Historial - Cobrador</title>
    <link rel="manifest" href="<?php echo base_url('manifest.json'); ?>">
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-indigo-600 text-white shadow-lg sticky top-0 z-50">
        <div class="px-4 py-3 flex items-center justify-between">
            <a href="<?php echo base_url('public/cobrador/home.php'); ?>" class="flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Volver</span>
            </a>
            <h1 class="text-lg font-bold">Historial</h1>
            <div class="w-16"></div>
        </div>
    </header>

    <!-- Contenido -->
    <main class="p-4 pb-24">
        <div id="historialList" class="space-y-3">
            <div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin"></i> Cargando...
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40">
        <div class="flex justify-around items-center py-2">
            <a href="<?php echo base_url('public/cobrador/home.php'); ?>" 
               class="flex flex-col items-center px-4 py-2 text-gray-600">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs">Inicio</span>
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
               class="flex flex-col items-center px-4 py-2 text-indigo-600">
                <i class="fas fa-history text-xl mb-1"></i>
                <span class="text-xs font-medium">Historial</span>
            </a>
        </div>
    </nav>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>';
        const USER_ID = <?php echo $user['id']; ?>;
        
        $(document).ready(function() {
            loadHistorial();
        });
        
        function loadHistorial() {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/pagos/list.php?limit=50&cobrador_id=' + USER_ID,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success && response.data.pagos.length > 0) {
                        renderHistorial(response.data.pagos);
                    } else {
                        $('#historialList').html('<div class="bg-white rounded-lg shadow p-4 text-center text-gray-500">No hay cobros registrados</div>');
                    }
                },
                error: function() {
                    $('#historialList').html('<div class="bg-white rounded-lg shadow p-4 text-center text-red-500">Error al cargar historial</div>');
                }
            });
        }
        
        function renderHistorial(pagos) {
            let html = '';
            pagos.forEach(function(pago) {
                const estadoClass = pago.estado === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                html += `
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="font-bold text-gray-800">${escapeHtml(pago.cliente_nombre || 'N/A')}</h3>
                                <p class="text-sm text-gray-500">${escapeHtml(pago.numero_prestamo || '')}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded ${estadoClass}">
                                ${escapeHtml(pago.estado || '')}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                <p><i class="fas fa-calendar"></i> ${formatDate(pago.fecha_pago)}</p>
                                ${pago.monto_mora > 0 ? `<p class="text-red-600"><i class="fas fa-exclamation-triangle"></i> Mora: S/ ${parseFloat(pago.monto_mora).toFixed(2)}</p>` : ''}
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600 text-lg">S/ ${parseFloat(pago.monto_pagado || 0).toFixed(2)}</p>
                            </div>
                        </div>
                        ${pago.comprobante_url ? `
                            <div class="mt-2">
                                <a href="${escapeHtml(pago.comprobante_url)}" target="_blank" class="text-blue-600 text-sm">
                                    <i class="fas fa-image"></i> Ver comprobante
                                </a>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            $('#historialList').html(html);
        }
        
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
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text || '').replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</body>
</html>

