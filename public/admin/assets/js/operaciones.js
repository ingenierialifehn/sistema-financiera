/**
 * Operaciones de Agencia - JavaScript Mejorado
 * Incluye información detallada de desembolsos
 */

$(document).ready(function () {
    loadDashboard();
    loadProximosDesembolsos();
});

// Cargar datos del dashboard con información detallada
function loadDashboard() {
    $.get(BASE_URL + '/app/api/operaciones/get_dashboard.php', function (response) {
        if (response.success) {
            const data = response.data;

            // === SALDOS ===
            $('#saldoBoveda').html('L. ' + parseFloat(data.saldo_boveda).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#saldoBovedaDetalle').html('L. ' + parseFloat(data.saldo_boveda).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            if (data.saldo_caja_operativa !== undefined) {
                $('#saldoCajaOperativa').html('L. ' + parseFloat(data.saldo_caja_operativa).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            if (data.fondos_disponibles !== undefined) {
                $('#fondosDisponibles').html('L. ' + parseFloat(data.fondos_disponibles).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // === ESTADÍSTICAS GENERALES ===
            $('#clientesTotales').text(data.clientes_totales);
            $('#creditosAprobados').text(data.creditos_aprobados);
            $('#carteraEnCalle').html('L. ' + parseFloat(data.cartera_en_calle).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // === INFORMACIÓN DETALLADA DE DESEMBOLSOS ===
            if (data.desembolsos) {
                const d = data.desembolsos;

                // Cantidad de desembolsos pendientes
                $('#cantidadDesembolsos').text(d.cantidad_pendientes);
                $('#desembolsosPendientes').text(d.cantidad_pendientes);

                // Monto total de desembolsos (monto solicitado)
                $('#montoTotalDesembolsos').html('L. ' + parseFloat(d.monto_total_desembolsos).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Monto neto requerido (lo que realmente se entrega al cliente)
                $('#montoNetoRequerido').html('L. ' + parseFloat(d.monto_neto_requerido).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Gastos administrativos totales
                $('#totalGastosAdmin').html('L. ' + parseFloat(d.total_gastos_administrativos).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Seguros totales
                $('#totalSeguros').html('L. ' + parseFloat(d.total_seguros).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Validación de fondos suficientes
                if (d.fondos_suficientes) {
                    $('#alertaFondos').addClass('hidden');
                    $('#fondosSuficientes').removeClass('hidden').html(
                        '<div class="flex items-center text-green-700">' +
                        '<i class="fas fa-check-circle text-green-500 mr-2"></i>' +
                        '<span>Fondos suficientes para todos los desembolsos</span>' +
                        '</div>'
                    );
                } else {
                    $('#fondosSuficientes').addClass('hidden');
                    $('#alertaFondos').removeClass('hidden').html(
                        '<div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">' +
                        '<div class="flex">' +
                        '<i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>' +
                        '<div>' +
                        '<p class="text-sm font-bold text-red-700">Fondos Insuficientes</p>' +
                        '<p class="text-sm text-red-600 mt-1">Faltante: <strong>L. ' + parseFloat(d.faltante).toLocaleString('es-HN', { minimumFractionDigits: 2 }) + '</strong></p>' +
                        '<p class="text-xs text-red-500 mt-2">Debe jalar fondos desde banco antes de realizar desembolsos</p>' +
                        '</div>' +
                        '</div>' +
                        '</div>'
                    );
                }

                // Desglose por tipo de préstamo (si existe)
                if (d.por_tipo && d.por_tipo.length > 0) {
                    let desglose = '<div class="mt-2 text-xs text-gray-600">';
                    d.por_tipo.forEach(tipo => {
                        desglose += `<div class="flex justify-between py-1">
                            <span>${tipo.tipo}:</span>
                            <span class="font-semibold">${tipo.cantidad} (L. ${parseFloat(tipo.monto_total).toLocaleString('es-HN', { minimumFractionDigits: 2 })})</span>
                        </div>`;
                    });
                    desglose += '</div>';
                    $('#desglosePorTipo').html(desglose);
                }
            }
        } else {
            console.error('Error al cargar dashboard:', response.message);
            Swal.fire('Error', 'No se pudo cargar la información del dashboard', 'error');
        }
    }).fail(function () {
        console.error('Error de conexión al cargar dashboard');
        Swal.fire('Error', 'Error de conexión con el servidor', 'error');
    });
}

// Cargar próximos desembolsos
function loadProximosDesembolsos() {
    const tableBody = $('#desembolsosTableBody');

    $.get(BASE_URL + '/app/api/operaciones/get_proximos_desembolsos.php', function (response) {
        if (response.success) {
            const desembolsos = response.data.desembolsos;

            if (desembolsos.length === 0) {
                tableBody.html(`
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i>
                            <p>No hay desembolsos pendientes para la agencia</p>
                        </td>
                    </tr>
                `);
                return;
            }

            let rows = '';
            desembolsos.forEach(d => {
                const fecha = new Date(d.created_at).toLocaleDateString('es-HN');
                const monto = parseFloat(d.monto_prestado).toLocaleString('es-HN', { style: 'currency', currency: 'HNL' });

                rows += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${d.numero_prestamo}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${d.cliente_nombre}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${d.cliente_dni}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-bold">
                            ${monto}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${fecha}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            ${USER_PERMISSIONS.prepare_delivery ?
                        `<button onclick="prepararEntrega(${d.id})" 
                                class="text-indigo-600 hover:text-indigo-900">
                                <i class="fas fa-hand-holding-usd mr-1"></i>Preparar Entrega
                            </button>` : ''}
                        </td>
                    </tr>
                `;
            });

            tableBody.html(rows);
        } else {
            tableBody.html(`
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        Error al cargar desembolsos
                    </td>
                </tr>
            `);
        }
    }).fail(function () {
        tableBody.html(`
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-red-500">
                    Error de conexión
                </td>
            </tr>
        `);
    });
}

// Preparar entrega de préstamo
function prepararEntrega(prestamoId) {
    Swal.fire({
        title: 'Preparar Entrega',
        text: '¿Desea marcar este préstamo como listo para entrega?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, preparar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la lógica para preparar la entrega
            Swal.fire(
                'Preparado',
                'El préstamo está listo para entrega',
                'success'
            );
            loadProximosDesembolsos();
            loadDashboard();
        }
    });
}
