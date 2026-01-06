/**
 * Préstamos JavaScript - Gestión de préstamos
 */

let currentPage = 1;
let currentSearch = '';
let currentEstado = '';
let currentCliente = '';

// Inicializar
$(document).ready(function() {
    loadClientesForFilter();
    loadPrestamos();
    
    // Eventos
    $('#btnNuevoPrestamo').on('click', function() {
        openModal();
        initializeClienteSelect();
    });
    
    $('#btnBuscar').on('click', function() {
        currentPage = 1;
        currentSearch = $('#searchInput').val();
        currentEstado = $('#filterEstado').val();
        currentCliente = $('#filterCliente').val();
        loadPrestamos();
    });
    
    $('#prestamoForm').on('submit', function(e) {
        e.preventDefault();
        savePrestamo();
    });
    
    $('#btnCerrarModal, #btnCancelar').on('click', function() {
        closeModal();
    });
    
    // Calcular resumen al cambiar valores
    $('#montoPrestado, #tasaInteres, #periodoMeses, #modalidad').on('input change', function() {
        if (this.id === 'modalidad') {
            suggestRateByModalidad();
        }
        calculatePreview();
    });

    // Abono a capital
    $('#abonoForm').on('submit', function(e) {
        e.preventDefault();
        submitAbono();
    });

    // Refinanciar 50%
    $('#refiForm').on('submit', function(e) {
        e.preventDefault();
        submitRefi();
    });
});

// Cargar préstamos
function loadPrestamos(page = 1) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    let url = `${BASE_URL}/app/api/prestamos/list.php?page=${page}&limit=20`;
    if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
    }
    if (currentEstado) {
        url += `&estado=${encodeURIComponent(currentEstado)}`;
    }
    if (currentCliente) {
        url += `&cliente_id=${currentCliente}`;
    }
    
    $.ajax({
        url: url,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                renderPrestamos(response.data.prestamos);
                renderPagination(response.data.pagination);
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                showAlert('error', 'Error al cargar préstamos');
            }
        }
    });
}

// Renderizar tabla
function renderPrestamos(prestamos) {
    const tbody = $('#prestamosTableBody');
    
    if (!prestamos || prestamos.length === 0) {
        tbody.html('<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay préstamos registrados</td></tr>');
        return;
    }
    
    let html = '';
    prestamos.forEach(function(prestamo) {
        const estadoClass = getEstadoClass(prestamo.estado);
        const saldo = parseFloat(prestamo.saldo_pendiente || prestamo.monto_total);
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${escapeHtml(prestamo.numero_prestamo || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(prestamo.cliente_nombre || '')}</div>
                    <div class="text-sm text-gray-500">${escapeHtml(prestamo.codigo_cliente || '')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${formatMoney(prestamo.monto_prestado)}<br>
                    <span class="text-xs text-gray-500">Total: ${formatMoney(prestamo.monto_total)}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${prestamo.cuotas_pagadas || 0} / ${prestamo.total_cuotas || 0}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${saldo > 0 ? 'text-red-600' : 'text-green-600'}">
                    ${formatMoney(saldo)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(prestamo.estado || '')}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewPrestamo(${prestamo.id})" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="openAbonoModal(${prestamo.id})" class="text-green-600 hover:text-green-900 mr-3" title="Abono a capital">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <button onclick="openRefiModal(${prestamo.id})" class="text-purple-600 hover:text-purple-900 mr-3" title="Refinanciar 50%">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button onclick="editPrestamo(${prestamo.id})" class="text-blue-600 hover:text-blue-900 mr-3" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deletePrestamo(${prestamo.id})" class="text-red-600 hover:text-red-900" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

// Cargar clientes para filtro
function loadClientesForFilter() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/clientes/list.php?limit=1000`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                const select = $('#filterCliente');
                select.html('<option value="">Todos</option>');
                response.data.clientes.forEach(function(cliente) {
                    select.append(`<option value="${cliente.id}">${escapeHtml(cliente.nombre_completo)}</option>`);
                });
            }
        }
    });
}

// Inicializar Select2 para el cliente
function initializeClienteSelect() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    // Destruir Select2 si ya existe
    if ($('#clienteId').hasClass('select2-hidden-accessible')) {
        $('#clienteId').select2('destroy');
    }
    
    $('#clienteId').select2({
        ajax: {
            url: `${BASE_URL}/app/api/clientes/list.php`,
            headers: {
                'Authorization': 'Bearer ' + token
            },
            dataType: 'json',
            delay: 250,
            data: function (params) {
                console.log('Searching for:', params.term);
                return {
                    search: params.term,
                    page: params.page || 1,
                    limit: 20
                };
            },
            processResults: function (data) {
                console.log('API Response:', data);
                if (data.success) {
                    return {
                        results: data.data.clientes.map(function(cliente) {
                            return {
                                id: cliente.id,
                                text: `${cliente.nombre_completo} - ${cliente.codigo_cliente}`
                            };
                        }),
                        pagination: {
                            more: data.data.pagination.page < data.data.pagination.total_pages
                        }
                    };
                } else {
                    console.error('API Error:', data.message);
                    return { results: [] };
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr.responseText);
                return { results: [] };
            },
            cache: true
        },
        placeholder: 'Buscar cliente...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        language: {
            inputTooShort: function () {
                return 'Ingresa al menos 1 caracter para buscar';
            },
            searching: function () {
                return 'Buscando...';
            },
            noResults: function () {
                return 'No se encontraron clientes';
            }
        }
    });
}

// Calcular preview
function calculatePreview() {
    const monto = parseFloat($('#montoPrestado').val() || 0);
    const tasa = parseFloat($('#tasaInteres').val() || 0);
    const periodo = parseInt($('#periodoMeses').val() || 0);
    const modalidad = ($('#modalidad').val() || 'mensual');
    
    if (monto > 0 && periodo > 0) {
        // Interés simple
        const interes = monto * (tasa / 100) * (periodo / 12);
        const montoTotal = monto + interes;
        const numeroCuotas = (function(p, mod){
            switch(mod){
                case 'diario': return p * 20;
                case 'semanal': return p * 4;
                case 'catorcenal': return p * 2;
                case 'mensual': default: return p;
            }
        })(periodo, modalidad);
        const montoCuota = numeroCuotas > 0 ? (montoTotal / numeroCuotas) : 0;
        
        $('#montoTotalPreview').text(formatMoney(montoTotal));
        $('#montoCuotaPreview').text(formatMoney(montoCuota));
    } else {
        $('#montoTotalPreview').text('L 0.00');
        $('#montoCuotaPreview').text('L 0.00');
    }
}

// Abrir modal
function openModal(prestamo = null) {
    $('#prestamoForm')[0].reset();
    $('#prestamoId').val('');
    $('#modalTitle').text(prestamo ? 'Editar Préstamo' : 'Nuevo Préstamo');
    
    if (prestamo) {
        // Llenar datos
    }
    
    $('#prestamoModal').removeClass('hidden').addClass('flex');
    calculatePreview();
}

// Cerrar modal
function closeModal() {
    $('#prestamoModal').addClass('hidden').removeClass('flex');
    // Destruir Select2 para liberar recursos si está inicializado
    if ($('#clienteId').hasClass('select2-hidden-accessible')) {
        $('#clienteId').select2('destroy');
    }
}

// Guardar préstamo
function savePrestamo() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    const data = {
        cliente_id: parseInt($('#clienteId').val()),
        monto_prestado: parseFloat($('#montoPrestado').val()),
        tasa_interes: parseFloat($('#tasaInteres').val()),
        periodo_meses: parseInt($('#periodoMeses').val()),
        modalidad: $('#modalidad').val() || 'mensual',
        fecha_desembolso: $('#fechaDesembolso').val(),
        dia_pago: parseInt($('#diaPago').val()),
        observaciones: $('#observaciones').val()
    };
    
    $.ajax({
        url: `${BASE_URL}/app/api/prestamos/create.php`,
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Préstamo creado exitosamente. Las cuotas se generaron automáticamente.');
                closeModal();
                loadPrestamos(currentPage);
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON) {
                showAlert('error', xhr.responseJSON.message || 'Error al crear préstamo');
            } else {
                showAlert('error', 'Error al crear préstamo');
            }
        }
    });
}

// Ver préstamo
function viewPrestamo(id) {
    window.location.href = `${BASE_URL}/public/admin/prestamo-detalle.php?id=${id}`;
}

// Editar préstamo
function editPrestamo(id) {
    // Implementar edición
    showAlert('info', 'Funcionalidad de edición próximamente');
}

// Eliminar préstamo
function deletePrestamo(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción cancelará el préstamo permanentemente',
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
                url: `${BASE_URL}/app/api/prestamos/delete.php?id=${id}`,
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message || 'Préstamo eliminado exitosamente');
                        loadPrestamos(currentPage);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON) {
                        showAlert('error', xhr.responseJSON.message || 'Error al eliminar préstamo');
                    } else {
                        showAlert('error', 'Error al eliminar préstamo');
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
        html += `<button onclick="loadPrestamos(${pagination.page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Anterior</button>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="px-3 py-1 bg-indigo-600 text-white rounded">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
            html += `<button onclick="loadPrestamos(${i})" class="px-3 py-1 border rounded hover:bg-gray-100">${i}</button>`;
        }
    }
    
    if (pagination.page < pagination.total_pages) {
        html += `<button onclick="loadPrestamos(${pagination.page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Siguiente</button>`;
    }
    
    html += '</div></div>';
    container.html(html);
}

// Funciones helper
function getEstadoClass(estado) {
    const estados = {
        'activo': 'bg-green-100 text-green-800',
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'completado': 'bg-blue-100 text-blue-800',
        'cancelado': 'bg-gray-100 text-gray-800',
        'en_mora': 'bg-red-100 text-red-800'
    };
    return estados[estado] || 'bg-gray-100 text-gray-800';
}

function formatMoney(amount) {
    return 'L ' + parseFloat(amount || 0).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
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

// Sugerir tasa según modalidad
function suggestRateByModalidad() {
    const modalidad = $('#modalidad').val();
    const tasaField = $('#tasaInteres');
    
    // Tasas configuradas (valores por defecto)
    const tasasPorModalidad = {
        'diario': 2.5,
        'semanal': 5.0,
        'catorcenal': 8.0,
        'mensual': 15.0
    };
    
    if (modalidad && tasasPorModalidad[modalidad]) {
        // Solo sugerir si el campo está vacío o es 0
        if (!tasaField.val() || parseFloat(tasaField.val()) === 0) {
            tasaField.val(tasasPorModalidad[modalidad]);
            // Mostrar mensaje informativo
            showAlert('info', `Tasa sugerida para ${modalidad}: ${tasasPorModalidad[modalidad]}%`);
        }
    }
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

