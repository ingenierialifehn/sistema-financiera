/**
 * Dashboard JavaScript - Sistema Financiero
 * Maneja la carga de datos vía AJAX y renderizado
 */

let paymentsChart = null;

/**
 * Cargar resumen de métricas
 */
function loadSummary() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: BASE_URL + '/app/api/admin/summary.php',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                
                // Actualizar widgets
                $('#total_prestamos_activos').text(formatNumber(data.total_prestamos_activos));
                $('#cartera_total').text(formatMoney(data.cartera_total));
                $('#cobros_hoy').text(formatMoney(data.cobros_hoy));
                $('#cuotas_vencidas').text(formatNumber(data.cuotas_vencidas));
                $('#cobradores_activos').text(formatNumber(data.cobradores_activos));
            }
        },
        error: function(xhr) {
            console.error('Error al cargar resumen:', xhr);
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                showError('Error al cargar las métricas');
            }
        }
    });
}

/**
 * Cargar gráfica de pagos
 */
function loadChart(days = 30) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: BASE_URL + '/app/api/admin/chart_payments.php',
        method: 'GET',
        data: { days: days },
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success && response.data) {
                renderChart(response.data.labels, response.data.data);
            }
        },
        error: function(xhr) {
            console.error('Error al cargar gráfica:', xhr);
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            }
        }
    });
}

/**
 * Renderizar gráfica con Chart.js
 */
function renderChart(labels, data) {
    const ctx = document.getElementById('paymentsChart');
    
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (paymentsChart) {
        paymentsChart.destroy();
    }
    
    paymentsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cobros (L)',
                data: data,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'L ' + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'L ' + formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Cargar últimos pagos
 */
function loadLatestPayments() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: BASE_URL + '/app/api/admin/latest_payments.php',
        method: 'GET',
        data: { limit: 20 },
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success && response.data && response.data.payments) {
                renderPaymentsTable(response.data.payments);
            }
        },
        error: function(xhr) {
            console.error('Error al cargar últimos pagos:', xhr);
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                $('#paymentsTableBody').html(
                    '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error al cargar los pagos</td></tr>'
                );
            }
        }
    });
}

/**
 * Renderizar tabla de pagos
 */
function renderPaymentsTable(payments) {
    const tbody = $('#paymentsTableBody');
    
    if (!payments || payments.length === 0) {
        tbody.html(
            '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay pagos registrados</td></tr>'
        );
        return;
    }
    
    let html = '';
    payments.forEach(function(payment) {
        const fecha = formatDate(payment.fecha_pago);
        const montoTotal = payment.monto + (payment.monto_mora || 0);
        const estadoClass = getEstadoClass(payment.estado);
        const comprobanteHtml = payment.url_foto_comprobante 
            ? `<a href="${payment.url_foto_comprobante}" target="_blank" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-image"></i>
               </a>`
            : '<span class="text-gray-400">-</span>';
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(payment.cliente_nombre)}</div>
                    <div class="text-sm text-gray-500">${escapeHtml(payment.codigo_cliente || '')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${escapeHtml(payment.numero_prestamo || 'N/A')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${formatMoney(montoTotal)}</div>
                    ${payment.monto_mora > 0 ? `<div class="text-xs text-red-600">Mora: ${formatMoney(payment.monto_mora)}</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${fecha}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(payment.cobrador_nombre || 'N/A')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(payment.estado)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${comprobanteHtml}
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

/**
 * Funciones helper
 */
function formatMoney(amount) {
    return 'L ' + formatNumber(amount);
}

function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('es-HN', {
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

function getEstadoClass(estado) {
    const estados = {
        'confirmado': 'bg-green-100 text-green-800',
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'rechazado': 'bg-red-100 text-red-800'
    };
    return estados[estado] || 'bg-gray-100 text-gray-800';
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

function showError(message) {
    // Puedes implementar un toast o alert aquí
    console.error(message);
}

