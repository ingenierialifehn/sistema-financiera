<?php
/**
 * Registrar Pago - Cobrador PWA
 * Formulario con cámara, GPS y modo offline
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
    <title>Registrar Pago - Cobrador</title>
    <link rel="manifest" href="<?php echo base_url('manifest.json'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Fallback basic styles if JS/CDN blocked (keeps layout readable) */
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; margin:0; }
        .bg-white { background: #fff; }
        .bg-gray-100 { background: #f7fafc; }
        .bg-indigo-600 { background: #4f46e5; }
        .text-white { color: #fff; }
        .rounded-lg { border-radius: 0.5rem; }
        .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .p-4 { padding: 1rem; }
        .px-4 { padding-left:1rem;padding-right:1rem; }
        .py-3 { padding-top:.75rem;padding-bottom:.75rem; }
        .w-full { width:100%; }
        .mt-2 { margin-top: .5rem; }
        .mb-2 { margin-bottom: .5rem; }
        .hidden { display: none; }
        header a { color: #fff; text-decoration: none; }
    </style>
    <style>
        #cameraPreview {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 1000;
        }
        #cameraPreview video,
        #cameraPreview canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .camera-controls {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
            z-index: 1001;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-indigo-600 text-white shadow-lg sticky top-0 z-50">
        <div class="px-4 py-3 flex items-center justify-between">
            <a href="<?php echo base_url('public/cobrador/home.php'); ?>" class="flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Volver</span>
            </a>
            <h1 class="text-lg font-bold">Registrar Pago</h1>
            <div class="w-16"></div>
        </div>
    </header>

    <!-- Cámara Preview -->
    <div id="cameraPreview">
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="camera-controls">
            <button id="captureBtn" class="bg-white rounded-full p-4 shadow-lg">
                <i class="fas fa-camera text-2xl text-gray-800"></i>
            </button>
            <button id="cancelCameraBtn" class="bg-red-500 rounded-full p-4 shadow-lg">
                <i class="fas fa-times text-white text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Formulario -->
    <main class="p-4 pb-24">
        <form id="pagoForm" class="space-y-4">
            <!-- Cliente -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Cliente <span class="text-red-500">*</span>
                </label>
                <select id="clienteId" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Seleccionar cliente</option>
                </select>
            </div>

            <!-- Préstamo -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Préstamo <span class="text-red-500">*</span>
                </label>
                <select id="prestamoId" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        disabled>
                    <option value="">Seleccionar préstamo</option>
                </select>
            </div>

            <!-- Cuota -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Cuota <span class="text-red-500">*</span>
                </label>
                <select id="cuotaId" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        disabled>
                    <option value="">Seleccionar cuota</option>
                </select>
                <div id="cuotaInfo" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm hidden">
                    <div class="grid grid-cols-2 gap-2">
                        <div><span class="text-gray-600">Monto:</span> <span class="font-semibold" id="infoMonto">-</span></div>
                        <div><span class="text-gray-600">Saldo:</span> <span class="font-semibold text-red-600" id="infoSaldo">-</span></div>
                        <div><span class="text-gray-600">Vencimiento:</span> <span class="font-semibold" id="infoVencimiento">-</span></div>
                        <div><span class="text-gray-600">Días Mora:</span> <span class="font-semibold" id="infoMora">-</span></div>
                    </div>
                </div>
            </div>

            <!-- Monto -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Monto Pagado <span class="text-red-500">*</span>
                </label>
                <input type="number" id="montoPagado" step="0.01" min="0.01" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Fecha -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Pago <span class="text-red-500">*</span>
                </label>
                <input type="date" id="fechaPago" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Método de Pago -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Método de Pago
                </label>
                <select id="metodoPago" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="deposito">Depósito</option>
                </select>
            </div>

            <!-- Foto del Comprobante -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Foto del Comprobante <span class="text-red-500">*</span>
                </label>
                <div id="fotoContainer" class="space-y-3">
                    <div id="fotoPreview" class="hidden">
                        <img id="fotoImg" src="" alt="Preview" class="preview-image w-full">
                        <button type="button" id="removeFotoBtn" class="mt-2 w-full bg-red-500 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-trash"></i> Eliminar Foto
                        </button>
                    </div>
                    <button type="button" id="takePhotoBtn" 
                            class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md flex items-center justify-center space-x-2">
                        <i class="fas fa-camera"></i>
                        <span>Tomar Foto</span>
                    </button>
                </div>
                <input type="hidden" id="fotoData" required>
            </div>

            <!-- Ubicación GPS -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación GPS
                </label>
                <button type="button" id="getLocationBtn" 
                        class="w-full bg-green-600 text-white px-4 py-2 rounded-md flex items-center justify-center space-x-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Obtener Ubicación</span>
                </button>
                <div id="locationInfo" class="mt-2 text-sm text-gray-600 hidden">
                    <p>Lat: <span id="locationLat">-</span></p>
                    <p>Lng: <span id="locationLng">-</span></p>
                </div>
                <input type="hidden" id="locationLatInput">
                <input type="hidden" id="locationLngInput">
            </div>

            <!-- Observaciones -->
            <div class="bg-white rounded-lg shadow p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones
                </label>
                <textarea id="observaciones" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Botón Guardar -->
            <button type="submit" 
                    class="w-full bg-indigo-600 text-white px-4 py-3 rounded-lg font-bold text-lg shadow-lg">
                <i class="fas fa-save"></i> Guardar Pago
            </button>
        </form>
    </main>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-indigo-600 mb-3"></i>
            <p class="text-gray-700">Guardando pago...</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const BASE_URL = '<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>';
        const USER_ID = <?php echo $user['id']; ?>;
        let stream = null;
        let fotoData = null;
        
        $(document).ready(function() {
            // Establecer fecha actual
            $('#fechaPago').val(new Date().toISOString().split('T')[0]);
            
            // Cargar clientes
            loadClientes();
            
            // Eventos
            $('#clienteId').on('change', function() {
                loadPrestamos($(this).val());
            });
            
            $('#prestamoId').on('change', function() {
                loadCuotas($(this).val());
            });
            
            $('#cuotaId').on('change', function() {
                loadCuotaInfo($(this).val());
            });
            
            $('#takePhotoBtn').on('click', openCamera);
            $('#captureBtn').on('click', capturePhoto);
            $('#cancelCameraBtn').on('click', closeCamera);
            $('#removeFotoBtn').on('click', removeFoto);
            $('#getLocationBtn').on('click', getLocation);
            $('#pagoForm').on('submit', savePago);
        });
        
        // Cargar clientes
        function loadClientes() {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/clientes/list.php?limit=1000&cobrador_id=' + USER_ID,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        const select = $('#clienteId');
                        response.data.clientes.forEach(function(cliente) {
                            select.append(`<option value="${cliente.id}">${escapeHtml(cliente.nombre_completo)} - ${escapeHtml(cliente.codigo_cliente)}</option>`);
                        });
                    }
                },
                error: function() {
                    alert('Error al cargar clientes');
                }
            });
        }
        
        // Cargar préstamos
        function loadPrestamos(clienteId) {
            if (!clienteId) {
                $('#prestamoId').html('<option value="">Seleccionar préstamo</option>').prop('disabled', true);
                return;
            }
            
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/prestamos/list.php?limit=1000&cliente_id=' + clienteId + '&estado=activo',
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        const select = $('#prestamoId');
                        select.html('<option value="">Seleccionar préstamo</option>').prop('disabled', false);
                        response.data.prestamos.forEach(function(prestamo) {
                            select.append(`<option value="${prestamo.id}">${escapeHtml(prestamo.numero_prestamo)} - Saldo: S/ ${parseFloat(prestamo.saldo_pendiente || 0).toFixed(2)}</option>`);
                        });
                    }
                },
                error: function() {
                    alert('Error al cargar préstamos');
                }
            });
        }
        
        // Cargar cuotas
        function loadCuotas(prestamoId) {
            if (!prestamoId) {
                $('#cuotaId').html('<option value="">Seleccionar cuota</option>').prop('disabled', true);
                return;
            }
            
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: BASE_URL + '/app/api/prestamos/cuotas.php?prestamo_id=' + prestamoId,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        const select = $('#cuotaId');
                        select.html('<option value="">Seleccionar cuota</option>').prop('disabled', false);
                        response.data.cuotas.forEach(function(cuota) {
                            const saldo = parseFloat(cuota.monto_cuota) - parseFloat(cuota.monto_pagado || 0);
                            const estado = cuota.estado === 'pagada' ? ' (Pagada)' : '';
                            select.append(`<option value="${cuota.id}" data-cuota='${JSON.stringify(cuota)}'>Cuota ${cuota.numero_cuota} - S/ ${parseFloat(cuota.monto_cuota).toFixed(2)}${estado}</option>`);
                        });
                    }
                },
                error: function() {
                    alert('Error al cargar cuotas');
                }
            });
        }
        
        // Cargar info de cuota
        function loadCuotaInfo(cuotaId) {
            const option = $(`#cuotaId option[value="${cuotaId}"]`);
            if (!option.length || !cuotaId) {
                $('#cuotaInfo').addClass('hidden');
                return;
            }
            
            const cuota = JSON.parse(option.attr('data-cuota'));
            const saldo = parseFloat(cuota.monto_cuota) - parseFloat(cuota.monto_pagado || 0);
            const hoy = new Date();
            const vencimiento = new Date(cuota.fecha_vencimiento);
            const diasMora = Math.max(0, Math.floor((hoy - vencimiento) / (1000 * 60 * 60 * 24)));
            
            $('#infoMonto').text('S/ ' + parseFloat(cuota.monto_cuota).toFixed(2));
            $('#infoSaldo').text('S/ ' + saldo.toFixed(2));
            $('#infoVencimiento').text(formatDate(cuota.fecha_vencimiento));
            $('#infoMora').text(diasMora + ' días');
            
            $('#cuotaInfo').removeClass('hidden');
            $('#montoPagado').attr('max', saldo).val(Math.min(saldo, parseFloat(cuota.monto_cuota)));
        }
        
        // Abrir cámara
        function openCamera() {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(function(mediaStream) {
                    stream = mediaStream;
                    $('#video')[0].srcObject = stream;
                    $('#cameraPreview').css('display', 'flex');
                })
                .catch(function(err) {
                    alert('Error al acceder a la cámara: ' + err.message);
                });
        }
        
        // Capturar foto
        function capturePhoto() {
            const video = $('#video')[0];
            const canvas = $('#canvas')[0];
            const context = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0);
            
            // Convertir a base64
            fotoData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Mostrar preview
            $('#fotoImg').attr('src', fotoData);
            $('#fotoPreview').removeClass('hidden');
            $('#takePhotoBtn').addClass('hidden');
            $('#fotoData').val(fotoData);
            
            closeCamera();
        }
        
        // Cerrar cámara
        function closeCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            $('#cameraPreview').hide();
        }
        
        // Remover foto
        function removeFoto() {
            fotoData = null;
            $('#fotoPreview').addClass('hidden');
            $('#takePhotoBtn').removeClass('hidden');
            $('#fotoData').val('');
        }
        
        // Obtener ubicación GPS
        function getLocation() {
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización');
                return;
            }
            
            $('#getLocationBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Obteniendo...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    $('#locationLat').text(lat.toFixed(6));
                    $('#locationLng').text(lng.toFixed(6));
                    $('#locationLatInput').val(lat);
                    $('#locationLngInput').val(lng);
                    $('#locationInfo').removeClass('hidden');
                    
                    $('#getLocationBtn').prop('disabled', false).html('<i class="fas fa-map-marker-alt"></i> Ubicación Obtenida');
                },
                function(error) {
                    alert('Error al obtener ubicación: ' + error.message);
                    $('#getLocationBtn').prop('disabled', false).html('<i class="fas fa-map-marker-alt"></i> Obtener Ubicación');
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
        }
        
        // Guardar pago
        function savePago(e) {
            e.preventDefault();
            
            const formData = {
                cuota_id: parseInt($('#cuotaId').val()),
                monto_pagado: parseFloat($('#montoPagado').val()),
                fecha_pago: $('#fechaPago').val(),
                metodo_pago: $('#metodoPago').val(),
                observaciones: $('#observaciones').val(),
                foto_data: fotoData,
                latitud: $('#locationLatInput').val() || null,
                longitud: $('#locationLngInput').val() || null
            };
            
            $('#loadingOverlay').removeClass('hidden').addClass('flex');
            
            if (navigator.onLine) {
                // Intentar guardar online
                savePagoOnline(formData);
            } else {
                // Guardar offline
                savePagoOffline(formData);
            }
        }
        
        // Guardar pago online
        function savePagoOnline(formData) {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            // Primero subir foto a Cloudinary
            uploadPhotoToCloudinary(formData.foto_data)
                .then(function(photoUrl) {
                    formData.comprobante_url = photoUrl;
                    delete formData.foto_data;
                    
                    // Luego guardar el pago
                    return $.ajax({
                        url: BASE_URL + '/app/api/pagos/create.php',
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Content-Type': 'application/json'
                        },
                        data: JSON.stringify(formData)
                    });
                })
                .then(function(response) {
                    if (response.success) {
                        alert('Pago registrado exitosamente');
                        window.location.href = BASE_URL + '/public/cobrador/home.php';
                    } else {
                        throw new Error(response.message || 'Error al guardar pago');
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    // Si falla, guardar offline
                    savePagoOffline(formData);
                })
                .finally(function() {
                    $('#loadingOverlay').addClass('hidden').removeClass('flex');
                });
        }
        
        // Guardar pago offline
        function savePagoOffline(formData) {
            // Guardar en IndexedDB para sincronización posterior
            savePagoToIndexedDB(formData)
                .then(function() {
                    alert('Pago guardado offline. Se sincronizará cuando haya conexión.');
                    window.location.href = BASE_URL + '/public/cobrador/home.php';
                })
                .catch(function(error) {
                    alert('Error al guardar pago offline: ' + error.message);
                })
                .finally(function() {
                    $('#loadingOverlay').addClass('hidden').removeClass('flex');
                });
        }
        
        // Subir foto a Cloudinary
        function uploadPhotoToCloudinary(base64Data) {
            return $.ajax({
                url: BASE_URL + '/app/api/upload/cloudinary.php',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({
                    image: base64Data,
                    folder: 'comprobantes-pagos'
                })
            }).then(function(response) {
                if (response.success && response.data.url) {
                    return response.data.url;
                } else {
                    throw new Error(response.message || 'Error al subir foto');
                }
            });
        }
        
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
    </script>
    <script src="<?php echo base_url('public/cobrador/assets/js/db.js'); ?>"></script>
</body>
</html>

