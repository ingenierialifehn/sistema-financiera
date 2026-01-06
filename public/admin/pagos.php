<?php
/**
 * Gestión de Pagos - Admin
 */

$pageTitle = 'Gestión de Pagos';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pagos</h2>
            <p class="text-gray-600">Registra y gestiona los pagos de préstamos</p>
        </div>
        <div class="flex space-x-3">
            <button id="btnNuevoPago" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Registrar Pago</span>
            </button>
            <button id="btnAbonoCapital" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                <i class="fas fa-coins"></i>
                <span>Abono a Capital</span>
            </button>
        </div>
    </div>
</div>

<!-- Búsqueda y filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" id="searchInput" placeholder="Cliente, préstamo..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select id="filterEstado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="confirmado">Confirmado</option>
                <option value="rechazado">Rechazado</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Préstamo</label>
            <select id="filterPrestamo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="flex items-end">
            <button id="btnBuscar" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>
</div>

<!-- Tabla de pagos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Préstamo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cobrador</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody id="pagosTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <div id="pagination" class="bg-gray-50 px-4 py-3 border-t border-gray-200"></div>
</div>

<!-- Modal: Abono a capital -->
<div id="abonoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-coins text-xl"></i>
                <h3 class="text-xl font-bold">Abono a Capital</h3>
            </div>
            <button onclick="closeAbonoModal()" class="text-white hover:text-gray-200 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="abonoForm" class="p-6">
            <input type="hidden" id="abonoPrestamoId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-hand-holding-usd text-green-600 mr-1"></i>
                        Préstamo *
                    </label>
                    <select id="abonoPrestamoSelect" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Seleccionar préstamo</option>
                    </select>
                    <div id="abonoPrestamoInfo" class="mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Saldo Actual:</span>
                                <span class="font-bold text-red-600 text-lg" id="abonoInfoSaldo">-</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Cuotas Restantes:</span>
                                <span class="font-bold text-gray-800 text-lg" id="abonoInfoCuotasRestantes">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-dollar-sign text-green-600 mr-1"></i>
                        Monto del Abono *
                    </label>
                    <input type="number" id="abonoMonto" step="0.01" min="0.01" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Este monto se descontará directamente del capital del préstamo
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-calendar text-green-600 mr-1"></i>
                        Fecha del Abono *
                    </label>
                    <input type="date" id="abonoFecha" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment text-green-600 mr-1"></i>
                        Observaciones
                    </label>
                    <textarea id="abonoObs" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Notas adicionales sobre el abono..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeAbonoModal()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="submit" 
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition font-medium">
                    <i class="fas fa-check mr-2"></i>Registrar Abono
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para registrar pago -->
<div id="pagoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Registrar Pago</h3>
            <button id="btnCerrarModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="pagoForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select id="clienteId" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar cliente</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Préstamo *</label>
                    <select id="prestamoId" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar préstamo</option>
                    </select>
                    <div id="prestamoInfo" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm hidden">
                        <h4 class="font-semibold text-green-800 mb-2">Información del Préstamo</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div><span class="text-gray-600">Número:</span> <span class="font-semibold" id="infoNumeroPrestamo">-</span></div>
                            <div><span class="text-gray-600">Monto:</span> <span class="font-semibold" id="infoMontoPrestamo">-</span></div>
                            <div><span class="text-gray-600">Total:</span> <span class="font-semibold" id="infoMontoTotal">-</span></div>
                            <div><span class="text-gray-600">Cuotas:</span> <span class="font-semibold" id="infoTotalCuotas">-</span></div>
                            <div><span class="text-gray-600">Pagadas:</span> <span class="font-semibold text-green-600" id="infoCuotasPagadas">-</span></div>
                            <div><span class="text-gray-600">Saldo:</span> <span class="font-semibold text-red-600" id="infoSaldoPrestamo">-</span></div>
                            <div><span class="text-gray-600">Desembolso:</span> <span class="font-semibold" id="infoFechaDesembolso">-</span></div>
                            <div><span class="text-gray-600">Vencimiento:</span> <span class="font-semibold" id="infoFechaVencimiento">-</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuota *</label>
                    <select id="cuotaId" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar cuota</option>
                    </select>
                    <div id="cuotaInfo" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm hidden">
                        <div class="grid grid-cols-2 gap-2">
                            <div><span class="text-gray-600">Monto Cuota:</span> <span class="font-semibold" id="infoMontoCuota">-</span></div>
                            <div><span class="text-gray-600">Monto Pagado:</span> <span class="font-semibold" id="infoMontoPagado">-</span></div>
                            <div><span class="text-gray-600">Saldo:</span> <span class="font-semibold text-red-600" id="infoSaldo">-</span></div>
                            <div><span class="text-gray-600">Vencimiento:</span> <span class="font-semibold" id="infoVencimiento">-</span></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado *</label>
                    <input type="number" id="montoPagado" step="0.01" min="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago *</label>
                    <input type="date" id="fechaPago" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                    <select id="metodoPago" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="deposito">Depósito</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Comprobante</label>
                    <input type="url" id="comprobanteUrl" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observaciones" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelar" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    <i class="fas fa-save"></i> Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Verificar que el archivo externo no cargó, incluir código directamente
if (typeof window.pagosJSLoaded === 'undefined') {
    console.log('Cargando pagos.js inline...');
    
    let currentPage = 1;
    let currentSearch = '';
    let currentEstado = '';
    let currentPrestamo = '';

    // Inicializar
    $(document).ready(function() {
        console.log('Documento listo, inicializando eventos...');
        
        loadPrestamosForFilter();
        loadPagos();
        
        // Eventos
        $('#btnNuevoPago').on('click', function() {
            loadClientesForSelect();
            openModal();
        });
        
        $('#btnAbonoCapital').on('click', function() {
            console.log('Botón Abono a Capital presionado');
            loadPrestamosForAbono();
            openAbonoModal();
        });
        
        $('#btnBuscar').on('click', function() {
            currentPage = 1;
            currentSearch = $('#searchInput').val();
            currentEstado = $('#filterEstado').val();
            currentPrestamo = $('#filterPrestamo').val();
            loadPagos();
        });
        
        $('#pagoForm').on('submit', function(e) {
            e.preventDefault();
            savePago();
        });
        
        $('#abonoForm').on('submit', function(e) {
            e.preventDefault();
            submitAbono();
        });
        
        $('#btnCerrarModal, #btnCancelar').on('click', function() {
            closeModal();
        });
        
        // Cargar info de préstamo para abono
        $('#abonoPrestamoSelect').on('change', function() {
            const prestamoId = $(this).val();
            if (prestamoId) {
                loadAbonoPrestamoInfo(prestamoId);
            } else {
                $('#abonoPrestamoInfo').addClass('hidden');
            }
        });
        
        // Cargar préstamos cuando cambia cliente
        $('#clienteId').on('change', function() {
            loadPrestamosForSelect($(this).val());
        });
        
        // Cargar cuotas cuando cambia préstamo
        $('#prestamoId').on('change', function() {
            const prestamoId = $(this).val();
            if (prestamoId) {
                loadPrestamoInfo(prestamoId);
                loadCuotasForSelect(prestamoId);
            } else {
                $('#prestamoInfo').addClass('hidden');
                $('#cuotaId').html('<option value="">Seleccionar cuota</option>');
                $('#cuotaInfo').addClass('hidden');
            }
        });
        
        // Cargar info de cuota cuando cambia
        $('#cuotaId').on('change', function() {
            loadCuotaInfo($(this).val());
        });
    });
    
    // Función para abrir modal de abono
    function openAbonoModal() {
        console.log('Abriendo modal de abono a capital');
        $('#abonoForm')[0].reset();
        $('#abonoPrestamoInfo').addClass('hidden');
        $('#abonoFecha').val(new Date().toISOString().split('T')[0]);
        $('#abonoModal').removeClass('hidden').addClass('flex');
    }
    
    // Función para cerrar modal de abono
    function closeAbonoModal() {
        $('#abonoModal').addClass('hidden').removeClass('flex');
    }
    
    // Funciones para pagos normales
    function loadClientesForSelect() {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/clientes/list.php?limit=1000&estado=activo`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success) {
                    const select = $('#clienteId');
                    select.html('<option value="">Seleccionar cliente</option>');
                    response.data.clientes.forEach(function(cliente) {
                        select.append(`<option value="${cliente.id}">${escapeHtml(cliente.nombre_completo)} - ${escapeHtml(cliente.codigo_cliente)}</option>`);
                    });
                }
            }
        });
    }

    function loadPrestamosForSelect(clienteId) {
        if (!clienteId) {
            $('#prestamoId').html('<option value="">Seleccionar préstamo</option>').prop('disabled', true);
            $('#prestamoInfo').addClass('hidden');
            $('#cuotaId').html('<option value="">Seleccionar cuota</option>').prop('disabled', true);
            $('#cuotaInfo').addClass('hidden');
            return;
        }
        
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/list.php?cliente_id=${clienteId}&estado=activo`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success) {
                    const select = $('#prestamoId');
                    select.html('<option value="">Seleccionar préstamo</option>').prop('disabled', false);
                    response.data.prestamos.forEach(function(prestamo) {
                        select.append(`<option value="${prestamo.id}">${escapeHtml(prestamo.numero_prestamo)} - ${formatMoney(prestamo.saldo_pendiente)} pendiente</option>`);
                    });
                }
            }
        });
    }

    function loadPrestamoInfo(prestamoId) {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/get.php?id=${prestamoId}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success && response.data) {
                    const prestamo = response.data;
                    
                    $('#infoNumeroPrestamo').text(prestamo.numero_prestamo || '-');
                    $('#infoMontoPrestamo').text(formatMoney(prestamo.monto_prestado));
                    $('#infoMontoTotal').text(formatMoney(prestamo.monto_total));
                    $('#infoTotalCuotas').text(prestamo.total_cuotas || 0);
                    $('#infoCuotasPagadas').text(prestamo.cuotas_pagadas || 0);
                    $('#infoSaldoPrestamo').text(formatMoney(prestamo.saldo_pendiente || 0));
                    $('#infoFechaDesembolso').text(prestamo.fecha_desembolso || '-');
                    $('#infoFechaVencimiento').text(prestamo.fecha_vencimiento || '-');
                    
                    $('#prestamoInfo').removeClass('hidden');
                }
            },
            error: function(xhr) {
                console.error('Error al cargar información del préstamo:', xhr);
                $('#prestamoInfo').addClass('hidden');
            }
        });
    }

    function loadCuotasForSelect(prestamoId) {
        if (!prestamoId) {
            $('#cuotaId').html('<option value="">Seleccionar cuota</option>').prop('disabled', true);
            $('#cuotaInfo').addClass('hidden');
            return;
        }
        
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/cuotas.php?prestamo_id=${prestamoId}`,
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
                        select.append(`<option value="${cuota.id}" data-cuota='${JSON.stringify(cuota)}'>Cuota ${cuota.numero_cuota} - ${formatMoney(cuota.monto_cuota)}${estado}</option>`);
                    });
                }
            }
        });
    }

    function loadCuotaInfo(cuotaId) {
        if (!cuotaId) {
            $('#cuotaInfo').addClass('hidden');
            return;
        }
        
        const selectedOption = $('#cuotaId option:selected');
        const cuotaData = selectedOption.data('cuota');
        
        if (cuotaData) {
            const saldo = parseFloat(cuotaData.monto_cuota) - parseFloat(cuotaData.monto_pagado || 0);
            
            $('#infoMontoCuota').text(formatMoney(cuotaData.monto_cuota));
            $('#infoMontoPagado').text(formatMoney(cuotaData.monto_pagado || 0));
            $('#infoSaldo').text(formatMoney(saldo));
            $('#infoVencimiento').text(cuotaData.fecha_vencimiento || '-');
            
            $('#cuotaInfo').removeClass('hidden');
            
            // Establecer el monto máximo a pagar
            $('#montoPagado').attr('max', saldo);
        }
    }

    function openModal() {
        $('#pagoForm')[0].reset();
        $('#prestamoInfo').addClass('hidden');
        $('#cuotaInfo').addClass('hidden');
        $('#fechaPago').val(new Date().toISOString().split('T')[0]);
        $('#pagoModal').removeClass('hidden').addClass('flex');
    }

    function closeModal() {
        $('#pagoModal').addClass('hidden').removeClass('flex');
    }

    function savePago() {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        const data = {
            cuota_id: parseInt($('#cuotaId').val()),
            monto_pagado: parseFloat($('#montoPagado').val()),
            fecha_pago: $('#fechaPago').val(),
            metodo_pago: $('#metodoPago').val(),
            comprobante_url: $('#comprobanteUrl').val(),
            observaciones: $('#observaciones').val()
        };
        
        $.ajax({
            url: `${BASE_URL}/app/api/pagos/create.php`,
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Pago registrado exitosamente');
                    closeModal();
                    loadPagos(currentPage);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON) {
                    showAlert('error', xhr.responseJSON.message || 'Error al registrar pago');
                } else {
                    showAlert('error', 'Error al registrar pago');
                }
            }
        });
    }

    function loadPrestamosForFilter() {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/list.php?limit=1000`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success) {
                    const select = $('#filterPrestamo');
                    select.html('<option value="">Todos los préstamos</option>');
                    response.data.prestamos.forEach(function(prestamo) {
                        select.append(`<option value="${prestamo.id}">${escapeHtml(prestamo.numero_prestamo)}</option>`);
                    });
                }
            }
        });
    }

    function loadPagos(page = 1) {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        let url = `${BASE_URL}/app/api/pagos/list.php?page=${page}&limit=20`;
        if (currentSearch) url += `&search=${currentSearch}`;
        if (currentEstado) url += `&estado=${currentEstado}`;
        if (currentPrestamo) url += `&prestamo_id=${currentPrestamo}`;
        
        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success) {
                    displayPagos(response.data.pagos);
                    displayPagination(response.data.pagination);
                }
            }
        });
    }

    function displayPagos(pagos) {
        const tbody = $('#pagosTableBody');
        tbody.empty();
        
        if (!pagos || pagos.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center py-4">No hay pagos registrados</td></tr>');
            return;
        }
        
        pagos.forEach(function(pago) {
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm">${pago.numero_prestamo}</td>
                    <td class="px-4 py-3 text-sm">${escapeHtml(pago.cliente_nombre)}</td>
                    <td class="px-4 py-3 text-sm">${formatMoney(pago.monto_pagado)}</td>
                    <td class="px-4 py-3 text-sm">${formatDate(pago.fecha_pago)}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 text-xs rounded-full ${getEstadoClass(pago.estado)}">
                            ${pago.estado}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">${formatDate(pago.created_at)}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function displayPagination(pagination) {
        const container = $('#pagination');
        container.empty();
        
        if (!pagination || pagination.total_pages <= 1) return;
        
        let html = '<div class="flex justify-between items-center">';
        html += '<div class="text-sm text-gray-700">';
        html += `Mostrando ${pagination.from} a ${pagination.to} de ${pagination.total} resultados`;
        html += '</div>';
        
        html += '<div class="flex space-x-2">';
        
        if (pagination.current_page > 1) {
            html += `<button onclick="loadPagos(${pagination.current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Anterior</button>`;
        }
        
        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'bg-indigo-600 text-white' : 'border hover:bg-gray-100';
            html += `<button onclick="loadPagos(${i})" class="px-3 py-1 ${active} rounded">${i}</button>`;
        }
        
        if (pagination.current_page < pagination.total_pages) {
            html += `<button onclick="loadPagos(${pagination.current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Siguiente</button>`;
        }
        
        html += '</div></div>';
        container.html(html);
    }

    function getEstadoClass(estado) {
        const estados = {
            'confirmado': 'bg-green-100 text-green-800',
            'pendiente': 'bg-yellow-100 text-yellow-800',
            'rechazado': 'bg-red-100 text-red-800'
        };
        return estados[estado] || 'bg-gray-100 text-gray-800';
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-HN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Funciones para abono a capital
    function loadPrestamosForAbono() {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/list.php?limit=1000&estado=activo`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success) {
                    const select = $('#abonoPrestamoSelect');
                    select.html('<option value="">Seleccionar préstamo</option>');
                    response.data.prestamos.forEach(function(prestamo) {
                        const saldo = parseFloat(prestamo.saldo_pendiente || 0);
                        if (saldo > 0) {
                            select.append(`<option value="${prestamo.id}">${escapeHtml(prestamo.numero_prestamo)} - ${escapeHtml(prestamo.cliente_nombre)} - Saldo: ${formatMoney(saldo)}</option>`);
                        }
                    });
                }
            }
        });
    }

    function loadAbonoPrestamoInfo(prestamoId) {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/get.php?id=${prestamoId}`,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                console.log('Response del préstamo:', response);
                if (response.success && response.data) {
                    // El préstamo está directamente en data, no en data.prestamo
                    const prestamo = response.data;
                    const saldo = parseFloat(prestamo.saldo_pendiente || 0);
                    const cuotasRestantes = (prestamo.total_cuotas || 0) - (prestamo.cuotas_pagadas || 0);
                    
                    $('#abonoInfoSaldo').text(formatMoney(saldo));
                    $('#abonoInfoCuotasRestantes').text(cuotasRestantes);
                    $('#abonoPrestamoInfo').removeClass('hidden');
                    
                    // Establecer el monto máximo del abono
                    $('#abonoMonto').attr('max', saldo);
                } else {
                    console.error('Respuesta inválida:', response);
                    $('#abonoPrestamoInfo').addClass('hidden');
                }
            },
            error: function(xhr) {
                console.error('Error al cargar información del préstamo:', xhr);
                if (xhr.responseJSON) {
                    console.error('Error details:', xhr.responseJSON);
                }
                $('#abonoPrestamoInfo').addClass('hidden');
            }
        });
    }

    function submitAbono() {
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        
        const data = {
            prestamo_id: parseInt($('#abonoPrestamoSelect').val()),
            monto: parseFloat($('#abonoMonto').val()),
            fecha: $('#abonoFecha').val(),
            observaciones: $('#abonoObs').val()
        };
        
        console.log('Enviando abono:', data);
        
        $.ajax({
            url: `${BASE_URL}/app/api/prestamos/abonos_capital/create.php`,
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                console.log('Response del abono:', response);
                if (response.success) {
                    showAlert('success', 'Abono a capital registrado exitosamente');
                    closeAbonoModal();
                    loadPagos(currentPage);
                } else {
                    showAlert('error', response.message || 'Error al registrar abono');
                }
            },
            error: function(xhr) {
                console.error('Error al registrar abono:', xhr);
                if (xhr.responseJSON) {
                    console.error('Error details:', xhr.responseJSON);
                    showAlert('error', xhr.responseJSON.message || 'Error al registrar abono');
                } else {
                    showAlert('error', 'Error al registrar abono');
                }
            }
        });
    }
    
    // Funciones helper
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatMoney(amount) {
        return 'L ' + parseFloat(amount || 0).toLocaleString('es-HN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    function showAlert(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : type === 'warning' ? 'Advertencia' : 'Información',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
    
    // Marcar que el código ya se cargó
    window.pagosJSLoaded = true;
}
</script>
<script src="./assets/js/pagos.js" onerror="console.error('No se pudo cargar pagos.js externo');"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

