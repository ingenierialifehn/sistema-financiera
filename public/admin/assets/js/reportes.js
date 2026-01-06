/**
 * Reportes JavaScript
 */

let currentReport = 'cobros';
let charts = {};

// Inicializar
$(document).ready(function() {
    showReport('cobros');
});

// Mostrar reporte
function showReport(reportType) {
    currentReport = reportType;
    
    // Actualizar tabs
    $('.tab-button').removeClass('active text-indigo-600 border-indigo-600').addClass('text-gray-500');
    $(`#tab-${reportType}`).removeClass('text-gray-500').addClass('active text-indigo-600 border-indigo-600');
    
    // Cargar filtros y contenido
    loadFilters(reportType);
    loadReport(reportType);
}

// Reporte Resumen (país/agencia/cobrador)
function renderReportResumen(data) {
    const prestamos = data.prestamos || { total: 0, monto_prestado_total: 0, monto_total: 0, activos: 0, completados: 0, en_mora: 0 };
    const pagos = data.pagos || { count: 0, total: 0, mora_total: 0 };
    
    // Tarjetas de estadísticas
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Préstamos</p>
                    <p class="text-2xl font-bold text-indigo-600">${prestamos.total}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3"><i class="fas fa-hand-holding-usd text-indigo-600 text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Monto Prestado</p>
                    <p class="text-2xl font-bold text-green-600">L ${formatMoney(prestamos.monto_prestado_total)}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3"><i class="fas fa-coins text-green-600 text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pagos Confirmados</p>
                    <p class="text-2xl font-bold text-blue-600">L ${formatMoney(pagos.total)}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3"><i class="fas fa-check-circle text-blue-600 text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Mora</p>
                    <p class="text-2xl font-bold text-red-600">L ${formatMoney(pagos.mora_total)}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div>
            </div>
        </div>
    `);
    
    // Tabla detalle resumen
    const html = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Métrica</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr><td class="px-6 py-4 text-sm">Préstamos Activos</td><td class="px-6 py-4 text-sm">${prestamos.activos}</td></tr>
                    <tr><td class="px-6 py-4 text-sm">Préstamos Completados</td><td class="px-6 py-4 text-sm">${prestamos.completados}</td></tr>
                    <tr><td class="px-6 py-4 text-sm">Préstamos en Mora</td><td class="px-6 py-4 text-sm">${prestamos.en_mora}</td></tr>
                    <tr><td class="px-6 py-4 text-sm">Pagos (cantidad)</td><td class="px-6 py-4 text-sm">${pagos.count}</td></tr>
                    <tr><td class="px-6 py-4 text-sm">Pagos (monto)</td><td class="px-6 py-4 text-sm">L ${formatMoney(pagos.total)}</td></tr>
                    <tr><td class="px-6 py-4 text-sm">Mora Total</td><td class="px-6 py-4 text-sm text-red-600">L ${formatMoney(pagos.mora_total)}</td></tr>
                </tbody>
            </table>
        </div>
    `;
    $('#reportContent').html(html);
}

// Cargar filtros
function loadFilters(reportType) {
    let html = '';
    
    switch(reportType) {
        case 'resumen':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <div class="flex space-x-2">
                            <input type="text" id="pais" placeholder="HN" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" />
                            <button type="button" id="btnGuardarPais" class="px-3 py-2 border rounded hover:bg-gray-50" title="Guardar como predeterminado">Guardar</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Se guardará como país predeterminado en este navegador.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agencia ID</label>
                        <input type="number" id="agenciaId" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrador</label>
                        <select id="cobradorId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                        <input type="date" id="fechaDesde" value="${getFirstDayOfMonth()}" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                        <input type="date" id="fechaHasta" value="${getToday()}" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                    </div>
                    <div class="md:col-span-5 flex items-end justify-end">
                        <button onclick="loadReport('${reportType}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"><i class="fas fa-search"></i> Generar</button>
                    </div>
                </div>
            `;
            loadCobradoresForFilter();
            // Prefijar país por defecto desde localStorage
            setTimeout(function(){
                try {
                    const def = localStorage.getItem('default_country') || 'HN';
                    const $pais = $('#pais');
                    if ($pais.length) { $pais.val(def); }
                    $('#btnGuardarPais').on('click', function(){ saveDefaultCountry(); });
                } catch(e) { /* noop */ }
            }, 0);
            break;
        case 'cobros':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                        <input type="date" id="fechaDesde" value="${getFirstDayOfMonth()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                        <input type="date" id="fechaHasta" value="${getToday()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrador</label>
                        <select id="cobradorId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReport('${reportType}')" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            `;
            loadCobradoresForFilter();
            break;
            
        case 'mora':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrador</label>
                        <select id="cobradorId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Días Mínimos de Mora</label>
                        <input type="number" id="diasMoraMin" value="1" min="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReport('${reportType}')" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            `;
            loadCobradoresForFilter();
            break;
            
        case 'ingresos':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                        <input type="date" id="fechaDesde" value="${getFirstDayOfMonth()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                        <input type="date" id="fechaHasta" value="${getToday()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Agrupar Por</label>
                        <select id="agruparPor" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="dia">Día</option>
                            <option value="semana">Semana</option>
                            <option value="mes">Mes</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReport('${reportType}')" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            `;
            break;
            
        case 'cartera':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="activo">Activo</option>
                            <option value="completado">Completado</option>
                            <option value="en_mora">En Mora</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cobrador</label>
                        <select id="cobradorId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReport('${reportType}')" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            `;
            loadCobradoresForFilter();
            break;
            
        case 'cobradores':
            html = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                        <input type="date" id="fechaDesde" value="${getFirstDayOfMonth()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                        <input type="date" id="fechaHasta" value="${getToday()}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button onclick="loadReport('${reportType}')" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            <i class="fas fa-search"></i> Generar
                        </button>
                    </div>
                </div>
            `;
            break;
    }
    
    $('#filtersContainer').html(html);
}

// Cargar reporte
function loadReport(reportType) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    let url = `${BASE_URL}/app/api/reportes/${reportType}.php?`;
    
    // Agregar parámetros según el tipo
    switch(reportType) {
        case 'resumen':
            const pais = ($('#pais').val() || '').trim();
            const agencia = $('#agenciaId').val() || '';
            const cobrador = $('#cobradorId').val() || '';
            const fd = $('#fechaDesde').val() || '';
            const fh = $('#fechaHasta').val() || '';
            if (pais) url += `pais=${encodeURIComponent(pais)}&`;
            if (agencia) url += `agencia_id=${encodeURIComponent(agencia)}&`;
            if (cobrador) url += `cobrador_id=${encodeURIComponent(cobrador)}&`;
            if (fd) url += `fecha_desde=${encodeURIComponent(fd)}&`;
            if (fh) url += `fecha_hasta=${encodeURIComponent(fh)}&`;
            break;
        case 'cobros':
            url += `fecha_desde=${$('#fechaDesde').val()}&fecha_hasta=${$('#fechaHasta').val()}`;
            if ($('#cobradorId').val()) {
                url += `&cobrador_id=${$('#cobradorId').val()}`;
            }
            break;
        case 'mora':
            if ($('#cobradorId').val()) {
                url += `cobrador_id=${$('#cobradorId').val()}&`;
            }
            url += `dias_mora_min=${$('#diasMoraMin').val() || 1}`;
            break;
        case 'ingresos':
            url += `fecha_desde=${$('#fechaDesde').val()}&fecha_hasta=${$('#fechaHasta').val()}&agrupar_por=${$('#agruparPor').val() || 'dia'}`;
            break;
        case 'cartera':
            url += `estado=${$('#estado').val() || 'activo'}`;
            if ($('#cobradorId').val()) {
                url += `&cobrador_id=${$('#cobradorId').val()}`;
            }
            break;
        case 'cobradores':
            url += `fecha_desde=${$('#fechaDesde').val()}&fecha_hasta=${$('#fechaHasta').val()}`;
            break;
    }
    
    $('#reportContent').html('<div class="text-center py-12"><i class="fas fa-spinner fa-spin text-4xl text-indigo-600"></i></div>');
    
    $.ajax({
        url: url,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                renderReport(reportType, response.data);
            }
        },
        error: function(xhr) {
            $('#reportContent').html('<div class="text-center py-12 text-red-500">Error al cargar el reporte</div>');
        }
    });
}

// Renderizar reporte
function renderReport(reportType, data) {
    switch(reportType) {
        case 'resumen':
            renderReportResumen(data);
            break;
        case 'cobros':
            renderReportCobros(data);
            break;
        case 'mora':
            renderReportMora(data);
            break;
        case 'ingresos':
            renderReportIngresos(data);
            break;
        case 'cartera':
            renderReportCartera(data);
            break;
        case 'cobradores':
            renderReportCobradores(data);
            break;
    }
}

// Reporte de Cobros
function renderReportCobros(data) {
    const stats = data.estadisticas;
    
    // Estadísticas
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cobros</p>
                    <p class="text-2xl font-bold text-indigo-600">${stats.total_cobros}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Monto</p>
                    <p class="text-2xl font-bold text-green-600">L ${formatMoney(stats.total_monto)}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-coins text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Mora</p>
                    <p class="text-2xl font-bold text-red-600">L ${formatMoney(stats.total_mora)}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Confirmados</p>
                    <p class="text-2xl font-bold text-blue-600">L ${formatMoney(stats.total_confirmado)}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
    `);
    
    // Tabla
    let tableHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Préstamo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobrador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.cobros.forEach(function(cobro) {
        tableHtml += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(cobro.fecha_pago)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="font-medium">${escapeHtml(cobro.cliente_nombre)}</div>
                    <div class="text-gray-500">${escapeHtml(cobro.codigo_cliente)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${escapeHtml(cobro.numero_prestamo)} - Cuota ${cobro.numero_cuota}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">L ${formatMoney(cobro.monto_pagado)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">${cobro.monto_mora > 0 ? 'L ' + formatMoney(cobro.monto_mora) : '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${escapeHtml(cobro.cobrador_nombre || 'N/A')}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded ${cobro.estado === 'confirmado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${escapeHtml(cobro.estado)}
                    </span>
                </td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#reportContent').html(tableHtml);
}

// Reporte de Mora
function renderReportMora(data) {
    const stats = data.estadisticas;
    
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Cuotas en Mora</p>
                    <p class="text-2xl font-bold text-red-600">${stats.total_cuotas}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Clientes en Mora</p>
                    <p class="text-2xl font-bold text-orange-600">${stats.total_clientes}</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <i class="fas fa-users text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Saldo Pendiente</p>
                    <p class="text-2xl font-bold text-gray-600">L ${formatMoney(stats.total_saldo)}</p>
                </div>
                <div class="bg-gray-100 rounded-full p-3">
                    <i class="fas fa-wallet text-gray-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Mora</p>
                    <p class="text-2xl font-bold text-red-600">L ${formatMoney(stats.total_mora)}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    `);
    
    // Tabla de clientes en mora
    let tableHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuotas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Días Máx</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobrador</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.clientes_mora.forEach(function(cliente) {
        tableHtml += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="font-medium">${escapeHtml(cliente.cliente_nombre)}</div>
                    <div class="text-gray-500">${escapeHtml(cliente.codigo_cliente)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${cliente.total_cuotas}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">L ${formatMoney(cliente.total_saldo)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">L ${formatMoney(cliente.total_mora)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">${cliente.dias_mora_max} días</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${escapeHtml(cliente.cobrador_nombre || 'Sin asignar')}</td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#reportContent').html(tableHtml);
}

// Reporte de Ingresos
function renderReportIngresos(data) {
    const stats = data.estadisticas;
    
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Ingresos</p>
                    <p class="text-2xl font-bold text-green-600">L ${formatMoney(stats.total_ingresos)}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cobros</p>
                    <p class="text-2xl font-bold text-indigo-600">${stats.total_cobros}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Promedio Diario</p>
                    <p class="text-2xl font-bold text-blue-600">L ${formatMoney(stats.promedio_diario)}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-calculator text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Mora</p>
                    <p class="text-2xl font-bold text-red-600">L ${formatMoney(stats.total_mora)}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    `);
    
    // Gráfico y tabla
    let html = `
        <div class="mb-6">
            <canvas id="ingresosChart" height="80"></canvas>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobros</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mora</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.ingresos.forEach(function(ingreso) {
        html += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${escapeHtml(ingreso.periodo)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${ingreso.total_cobros}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">L ${formatMoney(ingreso.monto_confirmado)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">L ${formatMoney(ingreso.total_mora)}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#reportContent').html(html);
    
    // Crear gráfico
    if (charts['ingresos']) {
        charts['ingresos'].destroy();
    }
    
    const ctx = document.getElementById('ingresosChart');
    if (ctx) {
        charts['ingresos'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.ingresos.map(i => i.periodo),
                datasets: [{
                    label: 'Ingresos',
                    data: data.ingresos.map(i => i.monto_confirmado),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    }
}

// Reporte de Cartera
function renderReportCartera(data) {
    const stats = data.estadisticas;
    
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Préstamos</p>
                    <p class="text-2xl font-bold text-indigo-600">${stats.total_prestamos}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <i class="fas fa-hand-holding-usd text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cartera</p>
                    <p class="text-2xl font-bold text-green-600">L ${formatMoney(stats.total_cartera)}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Desembolsado</p>
                    <p class="text-2xl font-bold text-blue-600">L ${formatMoney(stats.total_desembolsado)}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">% Pagado</p>
                    <p class="text-2xl font-bold text-purple-600">${stats.porcentaje_pagado.toFixed(2)}%</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-percentage text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    `);
    
    // Tabla
    let tableHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Préstamo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuotas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobrador</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.prestamos.forEach(function(prestamo) {
        tableHtml += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">${escapeHtml(prestamo.numero_prestamo)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="font-medium">${escapeHtml(prestamo.cliente_nombre)}</div>
                    <div class="text-gray-500">${escapeHtml(prestamo.codigo_cliente)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">L ${formatMoney(prestamo.monto_prestado)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">L ${formatMoney(prestamo.saldo_pendiente)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${prestamo.cuotas_pagadas}/${prestamo.total_cuotas}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${escapeHtml(prestamo.cobrador_nombre || 'Sin asignar')}</td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#reportContent').html(tableHtml);
}

// Reporte de Cobradores
function renderReportCobradores(data) {
    const stats = data.estadisticas;
    
    $('#statsContainer').html(`
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cobradores</p>
                    <p class="text-2xl font-bold text-indigo-600">${stats.total_cobradores}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <i class="fas fa-user-tie text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cobrado</p>
                    <p class="text-2xl font-bold text-green-600">L ${formatMoney(stats.total_monto_periodo)}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cobros</p>
                    <p class="text-2xl font-bold text-blue-600">${stats.total_cobros_periodo}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Promedio/Cobrador</p>
                    <p class="text-2xl font-bold text-purple-600">L ${formatMoney(stats.promedio_por_cobrador)}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-calculator text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    `);
    
    // Tabla
    let tableHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobrador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clientes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Préstamos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cobros</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto Cobrado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Promedio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">% Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    data.cobradores.forEach(function(cobrador) {
        tableHtml += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">${escapeHtml(cobrador.cobrador_nombre)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${cobrador.clientes_activos}/${cobrador.total_clientes}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${cobrador.prestamos_activos}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${cobrador.cobros_periodo}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">L ${formatMoney(cobrador.monto_cobrado_periodo)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">L ${formatMoney(cobrador.promedio_por_cobro)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${cobrador.porcentaje_total.toFixed(2)}%</td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#reportContent').html(tableHtml);
}

// Cargar cobradores para filtros
function loadCobradoresForFilter() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/cobradores/list.php?limit=1000`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                const select = $('#cobradorId');
                if (select.length) {
                    select.append('<option value="">Todos</option>');
                    response.data.cobradores.forEach(function(cobrador) {
                        select.append(`<option value="${cobrador.id}">${escapeHtml(cobrador.nombre_completo)}</option>`);
                    });
                }
            }
        }
    });
}

// Helper functions
function getFirstDayOfMonth() {
    const date = new Date();
    return date.toISOString().slice(0, 7) + '-01';
}

function getToday() {
    return new Date().toISOString().split('T')[0];
}

function formatMoney(amount) {
    return parseFloat(amount || 0).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-HN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function escapeHtml(text) {
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Guardar país predeterminado en localStorage
function saveDefaultCountry() {
    try {
        const val = ($('#pais').val() || '').trim().toUpperCase();
        if (!val) return;
        localStorage.setItem('default_country', val);
        const btn = $('#btnGuardarPais');
        if (btn.length) {
            const prev = btn.text();
            btn.prop('disabled', true).text('Guardado');
            setTimeout(()=>{ btn.prop('disabled', false).text(prev); }, 1200);
        }
    } catch(e) { /* noop */ }
}

