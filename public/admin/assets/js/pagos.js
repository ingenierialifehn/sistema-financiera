/**
 * Pagos JavaScript - Gestión de pagos
 */

console.log('Pagos.js cargado correctamente');

let currentPage = 1;
let currentSearch = '';
let currentEstado = '';
let currentPrestamo = '';

// Inicializar
$(document).ready(function() {
    console.log('Documento listo, inicializando eventos...');
    
    // Verificar que jQuery está cargado
    if (typeof $ === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }
    
    // Verificar que el botón existe
    const btnAbono = $('#btnAbonoCapital');
    if (btnAbono.length === 0) {
        console.error('Botón #btnAbonoCapital no encontrado');
    } else {
        console.log('Botón #btnAbonoCapital encontrado');
    }
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

// Cargar pagos
function loadPagos(page = 1) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    let url = `${BASE_URL}/app/api/pagos/list.php?page=${page}&limit=20`;
    if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
    }
    if (currentEstado) {
        url += `&estado=${encodeURIComponent(currentEstado)}`;
    }
    if (currentPrestamo) {
        url += `&prestamo_id=${currentPrestamo}`;
    }
    
    $.ajax({
        url: url,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                renderPagos(response.data.pagos);
                renderPagination(response.data.pagination);
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                showAlert('error', 'Error al cargar pagos');
            }
        }
    });
}

// Renderizar tabla
function renderPagos(pagos) {
    const tbody = $('#pagosTableBody');
    
    if (!pagos || pagos.length === 0) {
        tbody.html('<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No hay pagos registrados</td></tr>');
        return;
    }
    
    let html = '';
    pagos.forEach(function(pago) {
        const estadoClass = getEstadoClass(pago.estado);
        const montoTotal = parseFloat(pago.monto_pagado) + parseFloat(pago.monto_mora || 0);
        const fecha = formatDate(pago.fecha_pago);
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${fecha}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(pago.cliente_nombre || '')}</div>
                    <div class="text-sm text-gray-500">${escapeHtml(pago.codigo_cliente || '')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(pago.numero_prestamo || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${formatMoney(pago.monto_pagado)}
                    ${pago.monto_mora > 0 ? `<div class="text-xs text-red-600">Mora: ${formatMoney(pago.monto_mora)}</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${pago.monto_mora > 0 ? formatMoney(pago.monto_mora) : '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(pago.cobrador_nombre || 'N/A')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(pago.estado || '')}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    ${pago.comprobante_url ? `<a href="${escapeHtml(pago.comprobante_url)}" target="_blank" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-image"></i></a>` : ''}
                    <button onclick="deletePago(${pago.id})" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

// Cargar préstamos para filtro
function loadPrestamosForFilter() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/prestamos/list.php?limit=1000&estado=activo`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                const select = $('#filterPrestamo');
                select.html('<option value="">Todos</option>');
                response.data.prestamos.forEach(function(prestamo) {
                    select.append(`<option value="${prestamo.id}">${escapeHtml(prestamo.numero_prestamo)} - ${escapeHtml(prestamo.cliente_nombre)}</option>`);
                });
            }
        }
    });
}

// Cargar clientes para select
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

// Cargar préstamos para select
function loadPrestamosForSelect(clienteId) {
    if (!clienteId) {
        $('#prestamoId').html('<option value="">Seleccionar préstamo</option>').prop('disabled', true);
        return;
    }
    
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/prestamos/list.php?limit=1000&cliente_id=${clienteId}&estado=activo`,
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

// Cargar información del préstamo
function loadPrestamoInfo(prestamoId) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/prestamos/get.php?id=${prestamoId}`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                const prestamo = response.data.prestamo;
                
                // Actualizar información del préstamo
                $('#infoNumeroPrestamo').text(prestamo.numero_prestamo || '-');
                $('#infoMontoPrestamo').text(formatMoney(prestamo.monto_prestado));
                $('#infoMontoTotal').text(formatMoney(prestamo.monto_total));
                $('#infoTotalCuotas').text(prestamo.total_cuotas || 0);
                $('#infoCuotasPagadas').text(prestamo.cuotas_pagadas || 0);
                $('#infoSaldoPrestamo').text(formatMoney(prestamo.saldo_pendiente || 0));
                $('#infoFechaDesembolso').text(prestamo.fecha_desembolso || '-');
                $('#infoFechaVencimiento').text(prestamo.fecha_vencimiento || '-');
                
                // Mostrar información
                $('#prestamoInfo').removeClass('hidden');
            }
        },
        error: function(xhr) {
            console.error('Error al cargar información del préstamo:', xhr);
            $('#prestamoInfo').addClass('hidden');
        }
    });
}

// Cargar cuotas para select
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

// Cargar info de cuota
function loadCuotaInfo(cuotaId) {
    const option = $(`#cuotaId option[value="${cuotaId}"]`);
    if (!option.length || !cuotaId) {
        $('#cuotaInfo').addClass('hidden');
        return;
    }
    
    const cuota = JSON.parse(option.attr('data-cuota'));
    const saldo = parseFloat(cuota.monto_cuota) - parseFloat(cuota.monto_pagado || 0);
    
    $('#infoMontoCuota').text(formatMoney(cuota.monto_cuota));
    $('#infoMontoPagado').text(formatMoney(cuota.monto_pagado || 0));
    $('#infoSaldo').text(formatMoney(saldo));
    $('#infoVencimiento').text(formatDate(cuota.fecha_vencimiento));
    
    $('#cuotaInfo').removeClass('hidden');
    $('#montoPagado').attr('max', saldo).val(Math.min(saldo, parseFloat(cuota.monto_cuota)));
}

// Abrir modal
function openModal() {
    $('#pagoForm')[0].reset();
    $('#cuotaId').html('<option value="">Seleccionar cuota</option>').prop('disabled', true);
    $('#prestamoId').html('<option value="">Seleccionar préstamo</option>').prop('disabled', true);
    $('#cuotaInfo').addClass('hidden');
    $('#fechaPago').val(new Date().toISOString().split('T')[0]);
    $('#pagoModal').removeClass('hidden').addClass('flex');
}

// Cerrar modal
function closeModal() {
    $('#pagoModal').addClass('hidden').removeClass('flex');
}

// Guardar pago
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

// Eliminar pago
function deletePago(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará el pago y revertirá el estado de la cuota',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: `${BASE_URL}/app/api/pagos/delete.php?id=${id}`,
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Pago eliminado exitosamente');
                        loadPagos(currentPage);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON) {
                        showAlert('error', xhr.responseJSON.message || 'Error al eliminar pago');
                    } else {
                        showAlert('error', 'Error al eliminar pago');
                    }
                }
            });
        }
    });
}

// Renderizar paginación
function renderPagination(pagination) {
    const container = $('#pagination');
    
    if (pagination.total_pages <= 1) {
        container.html('');
        return;
    }
    
    let html = '<div class="flex items-center justify-between">';
    html += `<div class="text-sm text-gray-700">Mostrando ${((pagination.page - 1) * pagination.limit) + 1} a ${Math.min(pagination.page * pagination.limit, pagination.total)} de ${pagination.total}</div>`;
    html += '<div class="flex space-x-2">';
    
    if (pagination.page > 1) {
        html += `<button onclick="loadPagos(${pagination.page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Anterior</button>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="px-3 py-1 bg-indigo-600 text-white rounded">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
            html += `<button onclick="loadPagos(${i})" class="px-3 py-1 border rounded hover:bg-gray-100">${i}</button>`;
        }
    }
    
    if (pagination.page < pagination.total_pages) {
        html += `<button onclick="loadPagos(${pagination.page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Siguiente</button>`;
    }
    
    html += '</div></div>';
    container.html(html);
}

// Funciones helper
function getEstadoClass(estado) {
    const estados = {
        'confirmado': 'bg-green-100 text-green-800',
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'rechazado': 'bg-red-100 text-red-800'
    };
    return estados[estado] || 'bg-gray-100 text-gray-800';
}

function formatMoney(amount) {
    return 'L ' + parseFloat(amount || 0).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
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

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
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
            if (response.success) {
                const prestamo = response.data.prestamo;
                const saldo = parseFloat(prestamo.saldo_pendiente || 0);
                const cuotasRestantes = (prestamo.total_cuotas || 0) - (prestamo.cuotas_pagadas || 0);
                
                $('#abonoInfoSaldo').text(formatMoney(saldo));
                $('#abonoInfoCuotasRestantes').text(cuotasRestantes);
                $('#abonoPrestamoInfo').removeClass('hidden');
                
                // Establecer el monto máximo del abono
                $('#abonoMonto').attr('max', saldo);
            }
        },
        error: function(xhr) {
            console.error('Error al cargar información del préstamo:', xhr);
            $('#abonoPrestamoInfo').addClass('hidden');
        }
    });
}

function openAbonoModal() {
    console.log('Abriendo modal de abono a capital');
    $('#abonoForm')[0].reset();
    $('#abonoPrestamoInfo').addClass('hidden');
    $('#abonoFecha').val(new Date().toISOString().split('T')[0]);
    $('#abonoModal').removeClass('hidden').addClass('flex');
}

function closeAbonoModal() {
    $('#abonoModal').addClass('hidden').removeClass('flex');
}

function submitAbono() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    const data = {
        prestamo_id: parseInt($('#abonoPrestamoSelect').val()),
        monto: parseFloat($('#abonoMonto').val()),
        fecha: $('#abonoFecha').val(),
        observaciones: $('#abonoObs').val()
    };
    
    $.ajax({
        url: `${BASE_URL}/app/api/prestamos/abonos_capital/create.php`,
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Abono a capital registrado exitosamente');
                closeAbonoModal();
                loadPagos(currentPage);
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON) {
                showAlert('error', xhr.responseJSON.message || 'Error al registrar abono');
            } else {
                showAlert('error', 'Error al registrar abono');
            }
        }
    });
}

function showAlert(type, message) {
    const icons = {
        'success': 'success',
        'error': 'error',
        'info': 'info',
        'warning': 'warning'
    };
    
    Swal.fire({
        icon: icons[type] || 'info',
        title: type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : type === 'warning' ? 'Advertencia' : 'Información',
        text: message,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

