/**
 * Control de Caja - JavaScript
 * Incluye calculadora de billetaje y gestión de apertura/cierre
 */

// Denominaciones de billetes y monedas en Lempiras
const DENOMINACIONES = [500, 200, 100, 50, 20, 10, 5, 1];

let cajaActual = null;
let saldoSistema = 0;
let saldoBoveda = 0;

$(document).ready(function () {
    cargarEstadoCaja();
    inicializarCalculadoras();
});

// Cargar estado actual de la caja
function cargarEstadoCaja() {
    $.get(BASE_URL + '/app/api/caja/get_estado.php', function (response) {
        if (response.success) {
            cajaActual = response.data;
            renderEstadoCaja();
        } else {
            mostrarError('Error al cargar estado de caja');
        }
    }).fail(function () {
        mostrarError('Error de conexión');
    });
}

// Renderizar estado de caja
function renderEstadoCaja() {
    let html = '';

    // Preparar saldos (disponibles tanto en Abierto como Cerrado gracias al update de API)
    const saldoBoveda = parseFloat(cajaActual.saldo_boveda || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });
    const saldoActualCaja = parseFloat(cajaActual.saldo_caja || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });
    const horaApertura = cajaActual.hora_apertura ? new Date(cajaActual.hora_apertura).toLocaleString('es-HN') : '-';
    const saldoApertura = cajaActual.saldo_apertura_fisico ? parseFloat(cajaActual.saldo_apertura_fisico).toLocaleString('es-HN', { minimumFractionDigits: 2 }) : '0.00';

    if (!cajaActual || cajaActual.estado === 'Cerrado') {
        // Caja cerrada
        html = `
            <div class="flex justify-center items-center h-64">
                 <div class="bg-white rounded-lg shadow-lg p-8 border-t-8 border-gray-400 text-center max-w-md w-full">
                    <div class="mb-4 inline-block p-4 bg-gray-100 rounded-full">
                        <i class="fas fa-store-slash text-gray-500 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Caja Cerrada</h3>
                    <p class="text-gray-500 mb-6">Debe abrir la caja para iniciar operaciones y movimientos de fondos.</p>
                    
                    ${USER_PERMISSIONS.open_cash ?
                `<button id="btnAbrirCaja" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold shadow-md transform hover:-translate-y-1">
                        <i class="fas fa-key mr-2"></i> Abrir Caja del Día
                    </button>` : '<p class="text-red-500 font-semibold">No tiene permisos para abrir la caja.</p>'}
                 </div>
            </div>
        `;
    } else {
        // Caja abierta
        html = `
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-600 text-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-3xl"></i>
                            <div>
                                <h3 class="text-xl font-bold">Caja Abierta</h3>
                                <p class="text-green-100 text-sm">Apertura: ${horaApertura}</p>
                            </div>
                        </div>
                        ${USER_PERMISSIONS.close_cash ?
                `<button id="btnCerrarCaja" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-lock"></i> Cerrar Caja
                        </button>` : ''}
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Apertura</p>
                            <p class="text-2xl font-bold text-gray-900">L. ${saldoApertura}</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Caja Actual</p>
                            <p class="text-2xl font-bold text-blue-600">L. ${saldoActualCaja}</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Bóveda</p>
                            <p class="text-2xl font-bold text-purple-600">L. ${saldoBoveda}</p>
                        </div>
                    </div>

                    <!-- Botones de Operaciones de Fondos -->
                    <div class="flex flex-wrap gap-3 justify-center border-t pt-4">
                        ${USER_PERMISSIONS.pull_funds_bank ?
                `<button id="btnJalarFondos" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-download"></i>
                            <span>Jalar Fondos Banco</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.withdraw_vault ?
                `<button id="btnRetiroBoveda" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Retirar de Bóveda a Caja</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.return_vault ?
                `<button id="btnDevolucionBoveda" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-undo"></i>
                            <span>Devolver a Bóveda</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.return_bank ?
                `<button id="btnDepositoBanco" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-university"></i>
                            <span>Devolver a Banco</span>
                        </button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    $('#estadoCajaContainer').html(html);

    // Bind eventos
    $('#btnAbrirCaja').on('click', abrirModalApertura);
    $('#btnCerrarCaja').on('click', abrirModalCierre);
    $('#btnJalarFondos').on('click', abrirModalFondos);
    $('#btnRetiroBoveda').on('click', abrirModalRetiro);
    $('#btnDevolucionBoveda').on('click', abrirModalDevolucion);
    $('#btnDepositoBanco').on('click', abrirModalDeposito);
}



$('#btnCerrarModalApertura, #btnCancelarApertura').on('click', cerrarModalApertura);
$('#btnCerrarModalCierre, #btnCancelarCierre').on('click', cerrarModalCierre);



function mostrarError(mensaje) {
    $('#estadoCajaContainer').html(`
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i> ${mensaje}
        </div>
    `);
}

/* ==========================================================================
   NUEVAS FUNCIONES: GESTIÓN DE FONDOS
   ========================================================================== */

// --- 1. Jalar Fondos (Banco -> Bóveda) ---

function abrirModalFondos() {
    loadBancos();
    checkSugerenciaFondos();
    $('#jalarFondosModal').removeClass('hidden').addClass('flex');
}

function checkSugerenciaFondos() {
    $.get(BASE_URL + '/app/api/caja/get_monto_sugerido.php', function (res) {
        if (res.success) {
            const data = res.data;
            const sugerido = parseFloat(data.monto_sugerido);

            // Always update labels to show transparency
            $('#lblTotalRequerido').text('L. ' + parseFloat(data.total_requerido).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#lblDisponibleLocal').text('L. ' + (parseFloat(data.saldo_boveda) + parseFloat(data.saldo_caja)).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#lblMontoSugerido').text('L. ' + sugerido.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

            // Show container always
            $('#sugerenciaFondosContainer').removeClass('hidden');

            if (sugerido > 0) {
                // Auto-fill and Lock
                $('#montoFondos').val(sugerido.toFixed(2)).prop('readonly', true).addClass('bg-gray-100 text-gray-500 cursor-not-allowed');

                // Edit button
                $('#btnUsarSugerido').html('<i class="fas fa-edit"></i> Editar Monto').show().off('click').on('click', function () {
                    $('#montoFondos').prop('readonly', false).removeClass('bg-gray-100 text-gray-500 cursor-not-allowed').focus();
                    $(this).hide();
                });
            } else {
                // If 0 or covered, show 0 but allow edit? Or keep 0?
                // User said "appear locked with the amount needed". If amount needed is 0, show 0 and lock?
                // Let's standard: If 0, it means fully covered. We can leave it 0 or empty for them to decide if they want extra.
                // But let's unlock it so they can pull explicitly if they want.
                $('#montoFondos').val('').prop('readonly', false).removeClass('bg-gray-100 text-gray-500 cursor-not-allowed');
                $('#lblMontoSugerido').text('L. 0.00 (Cubierto)');
                $('#btnUsarSugerido').hide();
            }
        }
    });
}

function closeModalFondos() {
    $('#jalarFondosModal').removeClass('flex').addClass('hidden');
    $('#jalarFondosForm')[0].reset();
}

$('#btnCerrarModalFondos, #btnCancelarFondos').on('click', closeModalFondos);

// Cargar lista de bancos
function loadBancos() {
    $.get(BASE_URL + '/app/api/boveda/get_bancos.php', function (response) {
        if (response.success) {
            const bancos = response.data.bancos;
            let options = '<option value="">Seleccione una cuenta...</option>';

            bancos.forEach(banco => {
                options += `<option value="${banco.id}" data-saldo="${banco.saldo_actual}">
                    ${banco.nombre_banco} - ${banco.numero_cuenta}
                </option>`;
            });

            $('#bancoIdFondos').html(options);
        }
    });
}

$('#jalarFondosForm').on('submit', function (e) {
    e.preventDefault();

    const bancoId = $('#bancoIdFondos').val();
    const monto = parseFloat($('#montoFondos').val());
    const referencia = $('#referenciaFondos').val().trim();
    const observaciones = $('#observacionesFondos').val().trim();

    if (!bancoId || !monto || monto <= 0) {
        Swal.fire('Error', 'Datos inválidos', 'error');
        return;
    }

    const saldoDisponible = parseFloat($('#bancoIdFondos').find(':selected').data('saldo'));
    if (monto > saldoDisponible) {
        Swal.fire('Error', 'El monto excede el saldo de la cuenta', 'error');
        return;
    }

    $.ajax({
        url: BASE_URL + '/app/api/boveda/registrar_ingreso.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ banco_id: bancoId, monto, referencia, observaciones }),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', 'Fondos jalados exitosamente', 'success');
                closeModalFondos();
                cargarEstadoCaja(); // Recargar saldos
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error en servidor', 'error');
        }
    });
});

// --- 2. Retiro de Bóveda (Bóveda -> Caja) ---

function abrirModalRetiro() {
    // Usar datos cargados en cajaActual
    if (!cajaActual) return;

    $('#modalSaldoBoveda').text('L. ' + parseFloat(cajaActual.saldo_boveda).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
    $('#modalSaldoCaja').text('L. ' + parseFloat(cajaActual.saldo_caja).toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    $('#retiroBovedaModal').removeClass('hidden').addClass('flex');
}

function closeModalRetiro() {
    $('#retiroBovedaModal').removeClass('flex').addClass('hidden');
    $('#retiroBovedaForm')[0].reset();
}

$('#btnCerrarModalRetiro, #btnCancelarRetiro').on('click', closeModalRetiro);

$('#retiroBovedaForm').on('submit', function (e) {
    e.preventDefault();
    const monto = parseFloat($('#montoRetiro').val());
    const observaciones = $('#observacionesRetiro').val().trim();

    if (!monto || monto <= 0) {
        Swal.fire('Error', 'Monto inválido', 'error');
        return;
    }

    // Validar vs Boveda localmente (también se valida en backend)
    if (monto > parseFloat(cajaActual.saldo_boveda)) {
        Swal.fire('Error', 'Saldo insuficiente en Bóveda', 'error');
        return;
    }

    $.ajax({
        url: BASE_URL + '/app/api/operaciones/retiro_boveda_caja.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ monto, observaciones }),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', 'Retiro realizado exitosamente', 'success');
                closeModalRetiro();
                cargarEstadoCaja();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error en servidor', 'error');
        }
    });
});


// --- 3. Devolución a Bóveda (Caja -> Bóveda) ---

function abrirModalDevolucion() {
    if (!cajaActual) return;

    $('#modalDevolucionSaldoCaja').text('L. ' + parseFloat(cajaActual.saldo_caja).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
    $('#devolucionBovedaModal').removeClass('hidden').addClass('flex');
}

function closeModalDevolucion() {
    $('#devolucionBovedaModal').removeClass('flex').addClass('hidden');
    $('#devolucionBovedaForm')[0].reset();
}

$('#btnCerrarModalDevolucion, #btnCancelarDevolucion').on('click', closeModalDevolucion);

$('#btnDevolverTodo').on('click', function () {
    if (cajaActual) {
        $('#montoDevolucion').val(cajaActual.saldo_caja);
    }
});

$('#devolucionBovedaForm').on('submit', function (e) {
    e.preventDefault();
    const monto = parseFloat($('#montoDevolucion').val());
    const observaciones = $('#observacionesDevolucion').val().trim();

    if (!monto || monto <= 0) {
        Swal.fire('Error', 'Monto inválido', 'error');
        return;
    }

    if (monto > parseFloat(cajaActual.saldo_caja)) {
        Swal.fire('Error', 'Saldo insuficiente en Caja', 'error');
        return;
    }

    $.ajax({
        url: BASE_URL + '/app/api/caja/devolucion_caja_boveda.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ monto, observaciones }),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', 'Dinero devuelto a bóveda exitosamente', 'success');
                closeModalDevolucion();
                cargarEstadoCaja();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error en servidor', 'error');
        }
    });

});

// Inicializar calculadoras de billetaje
function inicializarCalculadoras() {
    crearCalculadora('calculadoraBilletajeApertura', 'Apertura');
    crearCalculadora('calculadoraBilletajeCierre', 'Cierre');
}

// Crear calculadora de billetaje
function crearCalculadora(containerId, tipo) {
    let html = '';

    DENOMINACIONES.forEach(denom => {
        html += `
            <div class="flex items-center justify-between space-x-2">
                <label class="text-sm font-medium text-gray-700 w-20">L. ${denom}</label>
                <input type="number" 
                    class="cantidad-billete flex-1 px-2 py-1 border border-gray-300 rounded text-right focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    data-denominacion="${denom}"
                    data-tipo="${tipo}"
                    min="0" 
                    value="0"
                    placeholder="0">
                <span class="subtotal text-sm font-semibold text-gray-900 w-24 text-right">L. 0.00</span>
            </div>
        `;
    });

    $(`#${containerId}`).html(html);

    // Bind eventos de cálculo
    $(`#${containerId} .cantidad-billete`).on('input', function () {
        calcularTotal(tipo);
    });
}

// Calcular total del billetaje
function calcularTotal(tipo) {
    let total = 0;
    const selector = tipo === 'Apertura' ? '#calculadoraBilletajeApertura' : '#calculadoraBilletajeCierre';

    $(selector + ' .cantidad-billete').each(function () {
        const cantidad = parseInt($(this).val()) || 0;
        const denominacion = parseFloat($(this).data('denominacion'));
        const subtotal = cantidad * denominacion;

        // Actualizar subtotal
        $(this).closest('.flex').find('.subtotal').text('L. ' + subtotal.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

        total += subtotal;
    });

    // Actualizar total
    const totalId = tipo === 'Apertura' ? '#totalFisicoApertura' : '#totalFisicoCierre';
    $(totalId).text('L. ' + total.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    // Verificar diferencia
    verificarDiferencia(tipo, total);

    return total;
}

// Verificar diferencia entre físico y sistema
// Verificar diferencia entre físico y sistema
function verificarDiferencia(tipo, totalFisico) {
    let saldoReferencia = 0;

    if (tipo === 'Apertura') {
        saldoReferencia = saldoSistema; // Corrección: Debe coincidir con lo que hay en sistema (remanente de ayer)
    } else {
        // En cierre, verificamos contra el total de EFECTIVO de la agencia (Boveda + Caja)
        // Ya que la caja debe estar en 0, el fisico debe coincidir con lo que hay en Boveda
        saldoReferencia = saldoSistema + saldoBoveda;
    }

    const diferencia = totalFisico - saldoReferencia;
    const alertaId = tipo === 'Apertura' ? '#alertaDiferenciaApertura' : '#alertaDiferenciaCierre';
    const mensajeId = tipo === 'Apertura' ? '#mensajeDiferenciaApertura' : '#mensajeDiferenciaCierre';
    const requiredId = tipo === 'Apertura' ? '#requiredObsApertura' : '#requiredObsCierre';

    if (Math.abs(diferencia) > 0.01) {
        // Hay diferencia
        const mensaje = diferencia > 0
            ? `Existe un sobrante de L. ${Math.abs(diferencia).toLocaleString('es-HN', { minimumFractionDigits: 2 })}. Por favor, justifique en observaciones.`
            : `Existe un faltante de L. ${Math.abs(diferencia).toLocaleString('es-HN', { minimumFractionDigits: 2 })}. Por favor, justifique en observaciones.`;

        $(alertaId).removeClass('hidden');
        $(mensajeId).text(mensaje);
        $(requiredId).removeClass('hidden');
    } else {
        // No hay diferencia
        $(alertaId).addClass('hidden');
        $(requiredId).addClass('hidden');
    }
}

// Abrir modal de apertura
function abrirModalApertura() {
    // Obtener saldo del sistema
    $.get(BASE_URL + '/app/api/caja/get_saldo_sistema.php', function (response) {
        if (response.success) {
            saldoSistema = parseFloat(response.data.saldo);
            saldoBoveda = parseFloat(response.data.saldo_boveda || 0);
            $('#saldoSistemaApertura').text('L. ' + parseFloat(saldoSistema).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#saldoBovedaApertura').text('L. ' + parseFloat(saldoBoveda).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#fechaApertura').text(new Date().toLocaleDateString('es-HN'));

            // Limpiar formulario
            $('#calculadoraBilletajeApertura .cantidad-billete').val(0);
            $('#observacionesApertura').val('');
            calcularTotal('Apertura');

            $('#modalApertura').removeClass('hidden').addClass('flex');
        }
    });
}

// Abrir modal de cierre
function abrirModalCierre() {
    if (!cajaActual) return;

    // Obtener saldo actual del sistema
    $.get(BASE_URL + '/app/api/caja/get_saldo_sistema.php', function (response) {
        if (response.success) {
            saldoSistema = parseFloat(response.data.saldo); // Force float
            saldoBoveda = parseFloat(response.data.saldo_boveda || 0); // Force float
            $('#saldoSistemaCierre').text('L. ' + parseFloat(saldoSistema).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#saldoBovedaCierre').text('L. ' + parseFloat(saldoBoveda).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#saldoAperturaInfo').text('L. ' + parseFloat(cajaActual.saldo_apertura_fisico).toLocaleString('es-HN', { minimumFractionDigits: 2 }));
            $('#horaAperturaInfo').text(new Date(cajaActual.hora_apertura).toLocaleString('es-HN'));

            // Limpiar formulario
            $('#calculadoraBilletajeCierre .cantidad-billete').val(0);
            $('#observacionesCierre').val('');
            calcularTotal('Cierre');

            $('#modalCierre').removeClass('hidden').addClass('flex');
        }
    });
}

// Cerrar modales
function cerrarModalApertura() {
    $('#modalApertura').removeClass('flex').addClass('hidden');
}

function cerrarModalCierre() {
    $('#modalCierre').removeClass('flex').addClass('hidden');
}

$('#btnCerrarModalApertura, #btnCancelarApertura').on('click', cerrarModalApertura);
$('#btnCerrarModalCierre, #btnCancelarCierre').on('click', cerrarModalCierre);

// Enviar formulario de apertura
$('#formApertura').on('submit', function (e) {
    e.preventDefault();

    const totalFisico = calcularTotal('Apertura');
    const observaciones = $('#observacionesApertura').val().trim();
    const diferencia = totalFisico - saldoSistema; // Corrección: Debe coincidir con Saldo Sistema

    // Validar observaciones si hay diferencia
    if (Math.abs(diferencia) > 0.01 && !observaciones) {
        Swal.fire('Error', 'Debe ingresar observaciones cuando hay diferencia en el conteo', 'error');
        return;
    }

    const data = {
        saldo_apertura_sistema: saldoSistema,
        saldo_apertura_fisico: totalFisico,
        observaciones: observaciones
    };

    $.ajax({
        url: BASE_URL + '/app/api/caja/apertura.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', response.data.message || 'Caja abierta exitosamente', 'success');
                cerrarModalApertura();
                cargarEstadoCaja();
            } else {
                Swal.fire('Error', response.message || 'Error al abrir caja', 'error');
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message || 'Error al procesar la solicitud';
            Swal.fire('Error', msg, 'error');
        }
    });
});

// Enviar formulario de cierre
$('#formCierre').on('submit', function (e) {
    e.preventDefault();

    const totalFisico = calcularTotal('Cierre');
    const observaciones = $('#observacionesCierre').val().trim();
    const diferencia = totalFisico - (saldoSistema + saldoBoveda);

    // Validar observaciones si hay diferencia
    if (Math.abs(diferencia) > 0.01 && !observaciones) {
        Swal.fire('Error', 'Debe ingresar observaciones cuando hay diferencia en el conteo', 'error');
        return;
    }

    // Confirmar cierre
    Swal.fire({
        title: '¿Cerrar Caja?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, cerrar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const data = {
                id_control: cajaActual.id_control,
                saldo_cierre_sistema: saldoSistema,
                saldo_cierre_fisico: totalFisico,
                diferencia_cierre: diferencia,
                observaciones: observaciones
            };

            $.ajax({
                url: BASE_URL + '/app/api/caja/cierre.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: 'Caja cerrada correctamente',
                            icon: 'success',
                            confirmButtonText: 'Imprimir Reporte Cierre',
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar sin imprimir'
                        }).then((result) => {
                            if (result.isConfirmed && response.data.reporte_data) {
                                imprimirReporteCierre(response.data.reporte_data);
                            }
                            cerrarModalCierre();
                            cargarEstadoCaja();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Error al cerrar caja', 'error');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error al procesar la solicitud';
                    Swal.fire('Error', msg, 'error');
                }
            });
        }
    });
});

function mostrarError(mensaje) {
    $('#estadoCajaContainer').html(`
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i> ${mensaje}
        </div>
    `);
}

// --- 4. Depósito a Banco (Caja -> Banco) ---

function abrirModalDeposito() {
    if (!cajaActual) return;

    // Update available balance display
    $('#modalDepositoSaldoCaja').text('L. ' + parseFloat(cajaActual.saldo_caja).toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    loadBancosDeposito();
    $('#depositoBancoModal').removeClass('hidden').addClass('flex');
}

function closeModalDeposito() {
    $('#depositoBancoModal').removeClass('flex').addClass('hidden');
    $('#depositoBancoForm')[0].reset();
}

function loadBancosDeposito() {
    $.get(BASE_URL + '/app/api/boveda/get_bancos.php', function (response) {
        if (response.success) {
            const bancos = response.data.bancos;
            let options = '<option value="">Seleccione una cuenta...</option>';

            bancos.forEach(banco => {
                options += `<option value="${banco.id}">
                    ${banco.nombre_banco} - ${banco.numero_cuenta}
                </option>`;
            });

            $('#bancoIdDeposito').html(options);
        }
    });
}

$('#btnCerrarModalDeposito, #btnCancelarDeposito').on('click', closeModalDeposito);

$('#depositoBancoForm').on('submit', function (e) {
    e.preventDefault();
    const bancoId = $('#bancoIdDeposito').val();
    const monto = parseFloat($('#montoDeposito').val());
    const numeroDeposito = $('#numeroDeposito').val().trim();
    const observaciones = $('#observacionesDeposito').val().trim();

    if (!bancoId) {
        Swal.fire('Error', 'Debe seleccionar un banco', 'error');
        return;
    }

    if (!numeroDeposito) {
        Swal.fire('Error', 'Debe ingresar el número de comprobante/transferencia', 'error');
        return;
    }

    if (!monto || monto <= 0) {
        Swal.fire('Error', 'Monto inválido', 'error');
        return;
    }

    if (monto > parseFloat(cajaActual.saldo_caja)) {
        Swal.fire('Error', 'Saldo insuficiente en Caja', 'error');
        return;
    }

    $.ajax({
        url: BASE_URL + '/app/api/caja/deposito_banco.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            banco_id: bancoId,
            monto: monto,
            referencia: numeroDeposito,
            observaciones: observaciones
        }),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', 'Depósito registrado exitosamente', 'success');
                closeModalDeposito();
                cargarEstadoCaja();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error en servidor', 'error');
        }
    });
});


/* ==========================================================================
   REDEFINICIÓN Y NUEVAS FUNCIONES DE CUADRE
   ========================================================================== */

function renderEstadoCaja() {
    let html = '';

    const saldoBoveda = parseFloat(cajaActual.saldo_boveda || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });
    const saldoActualCaja = parseFloat(cajaActual.saldo_caja || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });
    const horaApertura = cajaActual.hora_apertura ? new Date(cajaActual.hora_apertura).toLocaleString('es-HN') : '-';
    const saldoApertura = cajaActual.saldo_apertura_fisico ? parseFloat(cajaActual.saldo_apertura_fisico).toLocaleString('es-HN', { minimumFractionDigits: 2 }) : '0.00';

    if (!cajaActual || cajaActual.estado === 'Cerrado') {
        html = `
            <div class="flex justify-center items-center h-64">
                 <div class="bg-white rounded-lg shadow-lg p-8 border-t-8 border-gray-400 text-center max-w-md w-full">
                    <div class="mb-4 inline-block p-4 bg-gray-100 rounded-full">
                        <i class="fas fa-store-slash text-gray-500 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Caja Cerrada</h3>
                    <p class="text-gray-500 mb-6">Debe abrir la caja para iniciar operaciones y movimientos de fondos.</p>
                    ${USER_PERMISSIONS.open_cash ?
                `<button id="btnAbrirCaja" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition font-semibold shadow-md transform hover:-translate-y-1">
                        <i class="fas fa-key mr-2"></i> Abrir Caja del Día
                    </button>` : '<p class="text-red-500 font-semibold">No tiene permisos para abrir la caja.</p>'}
                 </div>
            </div>
        `;
    } else {
        html = `
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-600 text-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-3xl"></i>
                            <div>
                                <h3 class="text-xl font-bold">Caja Abierta</h3>
                                <p class="text-green-100 text-sm">Apertura: ${horaApertura}</p>
                            </div>
                        </div>
                        ${USER_PERMISSIONS.close_cash ?
                `<button id="btnCerrarCaja" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-lock"></i> Cerrar Caja
                        </button>` : ''}
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Apertura</p>
                            <p class="text-2xl font-bold text-gray-900">L. ${saldoApertura}</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Caja Actual</p>
                            <p class="text-2xl font-bold text-blue-600">L. ${saldoActualCaja}</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Saldo Bóveda</p>
                            <p class="text-2xl font-bold text-purple-600">L. ${saldoBoveda}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 justify-center border-t pt-4">
                        ${USER_PERMISSIONS.pull_funds_bank ?
                `<button id="btnJalarFondos" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-download"></i>
                            <span>Jalar Fondos Banco</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.withdraw_vault ?
                `<button id="btnRetiroBoveda" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Retirar de Bóveda a Caja</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.return_vault ?
                `<button id="btnDevolucionBoveda" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-undo"></i>
                            <span>Devolver a Bóveda</span>
                        </button>` : ''}

                        ${USER_PERMISSIONS.return_bank ?
                `<button id="btnDepositoBanco" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-university"></i>
                            <span>Devolver a Banco</span>
                        </button>` : ''}

                        <button id="btnCuadreAsesores" class="bg-indigo-700 hover:bg-indigo-800 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2 shadow-sm">
                            <i class="fas fa-user-shield"></i>
                            <span>Cuadre Asesores</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    $('#estadoCajaContainer').html(html);

    $('#btnAbrirCaja').on('click', abrirModalApertura);
    $('#btnCerrarCaja').on('click', abrirModalCierre);
    $('#btnJalarFondos').on('click', abrirModalFondos);
    $('#btnRetiroBoveda').on('click', abrirModalRetiro);
    $('#btnDevolucionBoveda').on('click', abrirModalDevolucion);
    $('#btnDepositoBanco').on('click', abrirModalDeposito);
    $('#btnCuadreAsesores').on('click', abrirModalCuadre);
}

// --- LOGICA CUADRE ASESORES (CARRITO) ---

let asesoresData = [];
let itemsCuadre = []; // Lista de partidas a registrar
let asesorSeleccionadoStats = null; // Guardar estado inicial para cálculos visuales

function abrirModalCuadre() {
    // Reset Todo
    itemsCuadre = [];
    asesorSeleccionadoStats = null;
    renderItemsCuadre();

    $('#cuadreAsesoresForm')[0].reset();
    $('#recaudadoHoyDisplay').text('L. 0.00');
    $('#entregadoHoyDisplay').text('L. 0.00').removeClass('text-blue-600').addClass('text-green-600');
    $('#pendienteDisplay').text('L. 0.00');
    $('#infoRecaudadoContainer').addClass('hidden');

    cargarBancosCuadre();

    // Cargar Asesores
    let url = BASE_URL + '/app/api/caja/get_asesores_recaudo.php?v=' + new Date().getTime();
    if (typeof cajaActual !== 'undefined' && cajaActual && cajaActual.id_agencia) {
        url += '&id_agencia=' + cajaActual.id_agencia;
    }

    $.get(url, function (res) {
        if (res.success) {
            asesoresData = res.data;
            if (asesoresData.length === 0) {
                $('#asesorIdCuadre').html('<option value="">No hay asesores encontrados</option>');
                return;
            }
            let ops = '<option value="">Seleccione Asesor...</option>';
            res.data.forEach(a => {
                const tieneSaldo = parseFloat(a.pendiente || 0) > 0.01;
                const tieneDesembolsos = parseInt(a.desembolsos_hoy || 0) > 0;
                const tieneRecaudo = parseFloat(a.recaudado_hoy || 0) > 0;
                const tieneRechazos = a.rechazados_hoy && a.rechazados_hoy.length > 0;

                // Mostrar si no ha cuadrado Y (tiene dinero pendiente, entregó préstamos, recaudó hoy O tiene rechazos)
                if (!a.ya_cuadrado && (tieneSaldo || tieneDesembolsos || tieneRecaudo || tieneRechazos)) {
                    ops += `<option value="${a.id_usuario}">${a.nombre_completo}</option>`;
                }
            });
            $('#asesorIdCuadre').html(ops);
        } else {
            $('#asesorIdCuadre').html(`<option value="">Error: ${res.message || 'Desconocido'}</option>`);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error cargando asesores:", textStatus, errorThrown);
        $('#asesorIdCuadre').html('<option value="">Error de conexión / Server</option>');
    });

    $('#modalCuadreAsesores').removeClass('hidden').addClass('flex');
}

$('#asesorIdCuadre').on('change', function () {
    const id = $(this).val();
    const asesor = asesoresData.find(a => a.id_usuario == id);

    // Al cambiar asesor, reseteamos la lista para evitar mezclar
    if (itemsCuadre.length > 0) {
        if (!confirm('Cambiar de asesor limpiará la lista actual. ¿Continuar?')) {
            $(this).val(asesorSeleccionadoStats ? asesorSeleccionadoStats.id : '');
            return;
        }
        itemsCuadre = [];
        renderItemsCuadre();
    }

    if (asesor) {
        // Guardamos stats base (lo que viene de DB)
        asesorSeleccionadoStats = {
            id: id,
            recaudado: parseFloat(asesor.recaudado_hoy || 0),
            capital: parseFloat(asesor.capital_hoy || 0),
            interes: parseFloat(asesor.interes_hoy || 0),
            entregadoBase: parseFloat(asesor.entregado_hoy || 0),
            pendienteBase: parseFloat(asesor.pendiente || 0),
            desembolsosHoy: parseInt(asesor.desembolsos_hoy || 0)
        };
        actualizarKPIsSimulados();

        // Mostrar desglose en tooltip para evitar confusiones
        const desgloseRecaudo = `Capital: L. ${asesorSeleccionadoStats.capital.toLocaleString('es-HN', { minimumFractionDigits: 2 })} + Interés/Mora: L. ${asesorSeleccionadoStats.interes.toLocaleString('es-HN', { minimumFractionDigits: 2 })}`;
        $('#recaudadoHoyDisplay').attr('title', desgloseRecaudo);
        $('#recaudadoHoyDisplay').parent().attr('title', desgloseRecaudo);

        // SUGERENCIA AUTOMÁTICA DE PAGO TOTAL (Para evitar diferencias)
        if (asesorSeleccionadoStats.pendienteBase > 0) {
            $('#montoEfectivoCuadre').val(asesorSeleccionadoStats.pendienteBase.toFixed(2));
        } else {
            $('#montoEfectivoCuadre').val('');
        }

        $('#infoRecaudadoContainer').removeClass('hidden');

        // Render Rechazados / Devoluciones
        if (asesor.rechazados_hoy && asesor.rechazados_hoy.length > 0) {
            let html = '';
            asesor.rechazados_hoy.forEach(loan => {
                const monto = parseFloat(loan.monto);
                const isRechazado = loan.estado === 'Rechazado en Ruta';
                const labelState = isRechazado ? 'Rechazado' : 'No Entregado';
                const colorState = isRechazado ? 'text-red-500' : 'text-orange-500';

                html += `
                    <div class="flex justify-between items-center bg-white p-2 rounded border border-red-100 shadow-sm">
                        <div class="flex-1">
                            <p class="font-bold text-gray-800">${loan.nombre_completo}</p>
                            <p class="text-xs ${colorState}">Préstamo #${loan.id} - ${labelState}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="text-right">
                                <span class="font-bold text-red-700">L. ${monto.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                            </div>
                            <div class="flex space-x-1">
                                <button type="button" class="btn-add-rejected-cash bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded shadow"
                                    data-monto="${monto}" 
                                    data-id="${loan.id}"
                                    data-estado="${loan.estado}"
                                    data-ref="Devolución Préstamo #${loan.id} (${loan.nombre_completo})">
                                    <i class="fas fa-money-bill-wave"></i> Efvo
                                </button>
                                <button type="button" class="btn-add-rejected-bank bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded shadow"
                                    data-monto="${monto}" 
                                    data-id="${loan.id}"
                                    data-estado="${loan.estado}"
                                    data-ref="Devolución Préstamo #${loan.id} (${loan.nombre_completo})">
                                    <i class="fas fa-university"></i> Depo
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#rejectedLoansList').html(html);
            $('#rejectedLoansContainer').removeClass('hidden');
        } else {
            $('#rejectedLoansContainer').addClass('hidden');
        }

    } else {
        asesorSeleccionadoStats = null;
        $('#infoRecaudadoContainer').addClass('hidden');
        $('#rejectedLoansContainer').addClass('hidden');
    }
});

// Event delegation for "Agregar Efectivo" rejected loan
$(document).on('click', '.btn-add-rejected-cash', function () {
    const monto = parseFloat($(this).data('monto'));
    const ref = $(this).data('ref');
    const loanId = $(this).data('id');
    const loanEstado = $(this).data('estado');

    itemsCuadre.push({
        tipo: 'efectivo',
        monto: monto,
        detalle: ref,
        loan_id: loanId,
        loan_estado: loanEstado
    });

    renderItemsCuadre();
    $(this).closest('div.flex.justify-between').fadeOut(); // Hide row
});

// Event delegation for "Agregar Banco" rejected loan
$(document).on('click', '.btn-add-rejected-bank', function () {
    const monto = parseFloat($(this).data('monto'));
    const ref = $(this).data('ref');
    const loanId = $(this).data('id');
    const loanEstado = $(this).data('estado');

    // Pre-fill Bank Form
    $('#montoBancoCuadre').val(monto);
    $('#refBancoCuadre').val(ref);
    $('#bancoIdCuadre').focus();

    // Store temp data to attach when "Agregar Depósito" is clicked manually?
    // Problem: The manual "Agregar Deposito" button doesn't know about this loan.
    // Solution: We should probably add it immediately as a bank item BUT asking for bank info.
    // OR force the user to use the form.
    // Re-thinking: The "Depo" button on the row is a shortcut. 
    // If I use the logic below, I'm just filling the form. I LOSE the loan_id association if I just fill the form inputs.
    // I need a way to pass the loan_id to the manual add function.

    // Let's attach metadata to the form button or a hidden input.
    $('#form-temp-loan-id').val(loanId);
    $('#form-temp-loan-estado').val(loanEstado);

    // Highlight the helper
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Seleccione Banco y confirme agregando depósito',
        timer: 3000,
        showConfirmButton: false
    });

    $(this).closest('div.flex.justify-between').fadeOut();
});

function actualizarDatosAsesor(asesorId) {
    if (!asesorId) return;
    let url = BASE_URL + '/app/api/caja/get_asesores_recaudo.php?v=' + new Date().getTime();
    if (typeof cajaActual !== 'undefined' && cajaActual && cajaActual.id_agencia) {
        url += '&id_agencia=' + cajaActual.id_agencia;
    }

    $.get(url, function (res) {
        if (res.success) {
            asesoresData = res.data;
            // No triggereamos change para no borrar la lista si estamos en medio de algun proceso,
            // pero actualizamos los stats base si es el mismo asesor.
            if (asesorSeleccionadoStats && asesorSeleccionadoStats.id == asesorId) {
                const updated = res.data.find(a => a.id_usuario == asesorId);
                if (updated) {
                    asesorSeleccionadoStats.recaudado = parseFloat(updated.recaudado_hoy || 0);
                    asesorSeleccionadoStats.entregadoBase = parseFloat(updated.entregado_hoy || 0);
                    asesorSeleccionadoStats.pendienteBase = parseFloat(updated.pendiente || 0);
                    asesorSeleccionadoStats.desembolsosHoy = parseInt(updated.desembolsos_hoy || 0);
                    actualizarKPIsSimulados();
                }
            }
        }
    });
}

function cargarBancosCuadre() {
    $.get(BASE_URL + '/app/api/boveda/get_bancos.php', function (res) {
        if (res.success) {
            let ops = '<option value="">Seleccione Banco...</option>';
            if (res.data.bancos) {
                res.data.bancos.forEach(b => {
                    ops += `<option value="${b.id}">${b.nombre_banco} - ${b.numero_cuenta}</option>`;
                });
            }
            $('#bancoIdCuadre').html(ops);
        }
    });
}

// Recalcular visualmente sumando lo que está en el carrito
function actualizarKPIsSimulados() {
    if (!asesorSeleccionadoStats) return;

    let totalEnLista = itemsCuadre.reduce((sum, item) => sum + item.monto, 0);

    let entregadoSimulado = asesorSeleccionadoStats.entregadoBase + totalEnLista;
    let pendienteSimulado = asesorSeleccionadoStats.recaudado - entregadoSimulado;

    $('#recaudadoHoyDisplay').text('L. ' + asesorSeleccionadoStats.recaudado.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    // Destacar cambio visual y explicar origen
    const elEntregado = $('#entregadoHoyDisplay');
    elEntregado.text('L. ' + entregadoSimulado.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    // Tooltip explicativo
    const desglose = `Base BD: L. ${asesorSeleccionadoStats.entregadoBase.toLocaleString('es-HN', { minimumFractionDigits: 2 })} + Lista: L. ${totalEnLista.toLocaleString('es-HN', { minimumFractionDigits: 2 })}`;
    elEntregado.attr('title', desglose);
    elEntregado.parent().attr('title', desglose); // Tambien al contenedor padre por si acaso

    // Si hay items en lista, cambiamos color
    if (totalEnLista > 0) {
        elEntregado.removeClass('text-green-600').addClass('text-blue-600 font-extrabold');
    } else {
        elEntregado.removeClass('text-blue-600 font-extrabold').addClass('text-green-600');
    }

    const elPendiente = $('#pendienteDisplay');
    elPendiente.text('L. ' + pendienteSimulado.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    // Alerta visual si negativo (excedente)
    if (pendienteSimulado < 0) {
        elPendiente.removeClass('text-red-600').addClass('text-orange-500').attr('title', 'Saldo a favor (Excedente)');
    } else {
        elPendiente.removeClass('text-orange-500').addClass('text-red-600').attr('title', '');
    }
}


// --- GESTIÓN DE ITEMS ---

// Agregar Efectivo
$('#btnAgregarEfectivo').on('click', function () {
    if (!asesorSeleccionadoStats) { Swal.fire('Atención', 'Seleccione un asesor primero', 'warning'); return; }

    const monto = parseFloat($('#montoEfectivoCuadre').val());
    if (!monto || monto <= 0) { Swal.fire('Error', 'Monto inválido', 'error'); return; }

    itemsCuadre.push({
        tipo: 'efectivo',
        monto: monto,
        detalle: 'Efectivo (Cobranza)'
    });

    $('#montoEfectivoCuadre').val('').focus(); // Limpiar y mantener foco para entrada rapida
    renderItemsCuadre();
});

// Agregar Deposito
$('#btnAgregarBanco').on('click', function () {
    if (!asesorSeleccionadoStats) { Swal.fire('Atención', 'Seleccione un asesor primero', 'warning'); return; }

    const monto = parseFloat($('#montoBancoCuadre').val());
    const bancoId = $('#bancoIdCuadre').val();
    const bancoTexto = $('#bancoIdCuadre option:selected').text();
    const ref = $('#refBancoCuadre').val();

    if (!monto || monto <= 0) { Swal.fire('Error', 'Monto inválido', 'error'); return; }
    if (!bancoId) { Swal.fire('Error', 'Seleccione banco', 'error'); return; }
    if (!ref) { Swal.fire('Error', 'Ingrese referencia', 'error'); return; }

    // Check for temp loan data (from rejected/returned shortcuts)
    const tempLoanId = $('#form-temp-loan-id').val();
    const tempLoanState = $('#form-temp-loan-estado').val();

    itemsCuadre.push({
        tipo: 'banco',
        monto: monto,
        banco_id: bancoId,
        referencia: ref,
        detalle: `Banco: ${bancoTexto} (Ref: ${ref})`,
        loan_id: tempLoanId || null,
        loan_estado: tempLoanState || null
    });

    // Clear temp data
    $('#form-temp-loan-id').val('');
    $('#form-temp-loan-estado').val('');

    $('#montoBancoCuadre').val('');
    $('#refBancoCuadre').val('');
    $('#bancoIdCuadre').val('');
    renderItemsCuadre();
});

// Eliminar Item
$(document).on('click', '.btn-del-item', function () {
    const idx = $(this).data('idx');
    itemsCuadre.splice(idx, 1);
    renderItemsCuadre();
});

function renderItemsCuadre() {
    let html = '';
    let total = 0;

    if (itemsCuadre.length === 0) {
        html = '<tr id="emptyRow"><td colspan="4" class="px-3 py-4 text-center text-gray-400 text-xs italic">Agregue montos arriba</td></tr>';
    } else {
        itemsCuadre.forEach((item, idx) => {
            total += item.monto;
            const icon = item.tipo === 'efectivo' ? '<i class="fas fa-money-bill text-green-600"></i>' : '<i class="fas fa-university text-blue-600"></i>';

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-xs">${icon} ${item.tipo}</td>
                    <td class="px-3 py-2 text-xs text-gray-600 truncate max-w-[150px]" title="${item.detalle}">${item.detalle}</td>
                    <td class="px-3 py-2 text-right font-medium">L. ${item.monto.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" class="text-red-400 hover:text-red-600 btn-del-item" data-idx="${idx}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    $('#listaItemsCuadre').html(html);
    $('#granTotalCuadre').text('L. ' + total.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

    // Actualizar KPIs visuales cada vez que cambia la lista
    actualizarKPIsSimulados();
}


$('#btnCerrarModalCuadre, #btnCancelarCuadre').on('click', function () {
    $('#modalCuadreAsesores').removeClass('flex').addClass('hidden');
});

$('#cuadreAsesoresForm').on('submit', function (e) {
    e.preventDefault();

    const asesorId = $('#asesorIdCuadre').val();

    if (!asesorId) { Swal.fire('Error', 'Seleccione un asesor', 'error'); return; }
    if (itemsCuadre.length === 0) { Swal.fire('Error', 'Agregue al menos un monto a la lista', 'error'); return; }

    const payload = {
        asesor_id: asesorId,
        items: itemsCuadre
    };

    $.ajax({
        url: BASE_URL + '/app/api/caja/procesar_cuadre_asesor.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (res) {
            if (res.success) {
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: 'Movimientos registrados',
                    showConfirmButton: false,
                    timer: 1500,
                    toast: true
                });

                // Limpiar todo después de éxito
                itemsCuadre = [];
                renderItemsCuadre();

                // Actualizar saldos en pantalla sin cerrar modal
                actualizarDatosAsesor(asesorId);
                cargarEstadoCaja();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function () { Swal.fire('Error', 'Error de conexión', 'error'); }
    });
});

// Botón "Cuadrar Asesor" - Bloquea al asesor para cobros
$('#btnCuadrarAsesor').on('click', function () {
    const asesorId = $('#asesorIdCuadre').val();

    let isListaVacia = itemsCuadre.length === 0;

    if (!asesorId) {
        Swal.fire('Error', 'Seleccione un asesor', 'error');
        return;
    }

    // Si la lista está vacía, preguntar confirmación especial
    if (isListaVacia) {
        // Verificar si ya ha entregado algo hoy (usando los datos cargados)
        const entregadoPrevio = asesorSeleccionadoStats ? asesorSeleccionadoStats.entregadoBase : 0;
        const disbursements = asesorSeleccionadoStats ? asesorSeleccionadoStats.desembolsosHoy : 0;

        if (entregadoPrevio <= 0 && disbursements <= 0) {
            Swal.fire('Error', 'No hay montos registrados ni entregas previas para cuadrar.', 'error');
            return;
        }
    }

    // Calcular totales
    let montoEfectivo = 0;
    let montoBanco = 0;
    let bancoId = null;
    let referenciaBanco = null;

    itemsCuadre.forEach(item => {
        if (item.tipo === 'efectivo') {
            montoEfectivo += item.monto;
        } else if (item.tipo === 'banco') {
            montoBanco += item.monto;
            bancoId = item.banco_id; // Tomamos el último banco (podrías mejorar esto)
            referenciaBanco = item.referencia;
        }
    });

    // Mensaje dinámico
    let mensajeConfirm = '';
    if (isListaVacia) {
        mensajeConfirm = `<p class="text-orange-600 font-bold">¡Atención! No está registrando nuevos montos.</p>
                          <p>Se realizará el cuadre y bloqueo usando solo las entregas previas.</p>`;
    } else {
        mensajeConfirm = `<p class="mt-2"><strong>Nuevo Monto a Registrar:</strong> L ${(montoEfectivo + montoBanco).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>`;
    }

    // Confirmar acción
    Swal.fire({
        title: '¿Cuadrar Asesor?',
        html: `<p>Esta acción bloqueará al asesor para que no pueda hacer más cobros hoy.</p>
               ${mensajeConfirm}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, cuadrar y bloquear',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const payload = {
                id_asesor: asesorId,
                monto_efectivo: montoEfectivo,
                monto_banco: montoBanco,
                banco_id: bancoId,
                referencia_banco: referenciaBanco,
                observaciones: `Cuadre realizado con ${itemsCuadre.length} movimiento(s)`
            };

            $.ajax({
                url: BASE_URL + '/app/api/caja/cuadrar_asesor.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cuadre Exitoso!',
                            html: `<p>${res.message}</p>
                                   <p class="mt-2"><strong>Recaudado:</strong> L ${res.data.monto_recaudado.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                   <p><strong>Entregado:</strong> L ${res.data.monto_entregado.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                   <p><strong>Diferencia:</strong> L ${res.data.diferencia.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>`,
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            if (res.data && res.data.transacciones) {
                                imprimirTicketCuadre(res.data);
                            }
                        });

                        // Limpiar formulario
                        itemsCuadre = [];
                        renderItemsCuadre();
                        $('#cuadreAsesoresForm')[0].reset();
                        $('#modalCuadreAsesores').removeClass('flex').addClass('hidden');

                        // Actualizar estado de caja
                        cargarEstadoCaja();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error de conexión', 'error');
                }
            });
        }
    });
});

function imprimirTicketCuadre(data) {
    const anchoPagina = '80mm';
    const ventana = window.open('', '_blank', 'width=400,height=600');

    // Formatear transacciones
    let listaHtml = '';
    if (data.transacciones && data.transacciones.length > 0) {
        listaHtml = `
            <table style="width: 100%; font-size: 10px; border-collapse: collapse; margin-top: 5px;">
                <thead>
                    <tr style="border-bottom: 1px dashed #000;">
                        <td style="text-align: left;">Cte / Hora</td>
                        <td style="text-align: right;">Monto</td>
                    </tr>
                </thead>
                <tbody>
        `;
        data.transacciones.forEach(t => {
            listaHtml += `
                <tr>
                    <td style="padding: 2px 0;">
                        ${t.nombre_completo.substring(0, 15)}<br>
                        <span style="font-size: 9px; color: #555;">${t.hora}</span>
                    </td>
                    <td style="text-align: right; vertical-align: top;">L ${parseFloat(t.monto_pagado).toFixed(2)}</td>
                </tr>
            `;
        });
        listaHtml += `</tbody></table>`;
    } else {
        listaHtml = '<p style="text-align: center; font-size: 10px; margin: 5px 0;">- Sin transacciones registradas -</p>';
    }

    // Detalle Bancos
    let bancosHtml = '';
    if (data.detalle_bancos && data.detalle_bancos.length > 0) {
        bancosHtml = `
            <div style="margin-top: 5px; border-top: 1px dashed #ccc; padding-top: 2px;">
                <strong style="font-size: 10px;">Detalle Bancos:</strong>
                <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
        `;
        data.detalle_bancos.forEach(b => {
            bancosHtml += `
                <tr>
                    <td>${b.nombre_banco}</td>
                    <td style="text-align: right;">L ${parseFloat(b.total).toFixed(2)}</td>
                </tr>
             `;
        });
        bancosHtml += `</table></div>`;
    }

    // Desembolsos Entregados (Si existen)
    let desembolsosHtml = '';
    let totalDesembolsado = 0;
    if (data.desembolsos_entregados && data.desembolsos_entregados.length > 0) {
        desembolsosHtml = `
            <div style="margin-top: 5px; border-top: 1px dashed #ccc; padding-top: 2px;">
                <strong style="font-size: 10px;">Préstamos Entregados:</strong>
                <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px dotted #999;">
                            <td style="text-align: left;">Cliente</td>
                            <td style="text-align: right;">Monto</td>
                        </tr>
                    </thead>
                    <tbody>
        `;
        data.desembolsos_entregados.forEach(d => {
            const m = parseFloat(d.monto_capital);
            totalDesembolsado += m;
            desembolsosHtml += `
                <tr>
                    <td>${d.nombre_completo.substring(0, 18)}</td>
                    <td style="text-align: right;">L ${m.toFixed(2)}</td>
                </tr>
             `;
        });
        desembolsosHtml += `</tbody></table></div>`;
    }

    const html = `
    <html>
    <head>
        <title>Recibo de Cuadre</title>
        <style>
            body { 
                font-family: 'Courier New', Courier, monospace; 
                margin: 0; 
                padding: 5px; 
                width: 100%;
                font-size: 12px;
                color: #000;
            }
            @media print {
                @page { size: portrait; margin: 1cm; }
                body { padding: 1cm; }
            }
            .header { text-align: center; margin-bottom: 15px; }
            .header h4 { margin: 0; text-transform: uppercase; font-size: 14px; }
            .divider { border-top: 1px dashed #000; margin: 10px 0; }
            .total-row { display: flex; justify-content: space-between; font-weight: bold; margin-top: 5px; font-size: 13px; }
            .sub-row { display: flex; justify-content: space-between; font-size: 11px; margin-top: 2px; color: #333; }
            .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #555; }
        </style>
    </head>
    <body onload="window.print(); window.close();">
        <div class="header">
            <h4>Comprobante de Entrega</h4>
            <p style="margin: 2px;">Cuadre de Asesor</p>
            <p style="margin: 5px 0;">${data.fecha}</p>
            <p style="margin: 2px;"><strong>${data.asesor_nombre}</strong></p>
        </div>
        
        <div class="divider"></div>
        
        <div style="margin-bottom: 10px;">
            <strong>Detalle de Transacciones (Cobros):</strong>
            ${listaHtml}
        </div>
        
        ${desembolsosHtml}

        
        <div class="divider"></div>
        
        <div class="total-row">
            <span>Total Recaudado:</span>
            <span>L ${parseFloat(data.monto_recaudado).toFixed(2)}</span>
        </div>
        
        ${totalDesembolsado > 0 ? `
        <div class="total-row">
            <span>Total Préstamos Entregados:</span>
            <span>L ${totalDesembolsado.toFixed(2)}</span>
        </div>` : ''}
        
        <div class="divider"></div>

        <div class="total-row">
            <span>Dinero Devuelto (Operaciones):</span>
            <span>L ${parseFloat(data.monto_entregado).toFixed(2)}</span>
        </div>
        
        <div class="sub-row">
            <span>- Efectivo:</span>
            <span>L ${parseFloat(data.total_efectivo_dia || 0).toFixed(2)}</span>
        </div>
        <div class="sub-row">
            <span>- Depósitos:</span>
            <span>L ${parseFloat(data.total_banco_dia || 0).toFixed(2)}</span>
        </div>
        
        ${bancosHtml}
        
        <div class="divider"></div>

        ${parseFloat(data.diferencia) !== 0 ? `
        <div class="total-row" style="color: red;">
            <span>Diferencia:</span>
            <span>L ${parseFloat(data.diferencia).toFixed(2)}</span>
        </div>` : ''}
        
        <div class="footer">
            <p>ID Transacción: ${data.id_cuadre}</p>
            <br>
            <p>_________________________</p>
            <p>Firma Entregado (Asesor)</p>
        </div>
    </body>
    </html >
        `;

    ventana.document.write(html);
    ventana.document.close();
}

function imprimirReporteCierre(data) {
    const ventana = window.open('', '_blank', 'width=900,height=700');

    let filas = '';
    let totalRecaudado = 0;

    if (data.transacciones && data.transacciones.length > 0) {
        data.transacciones.forEach(t => {
            const monto = parseFloat(t.monto_pagado);
            totalRecaudado += monto;
            filas += `
        < tr >
                    <td>${t.hora}</td>
                    <td>${t.numero_cuota || '-'}</td>
                    <td>${t.cliente}</td>
                    <td>${t.cobrador}</td>
                    <td class="text-right">L. ${monto.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                </tr >
        `;
        });
    } else {
        filas = '<tr><td colspan="5" class="text-center">No hay transacciones registradas hoy</td></tr>';
    }

    const html = `
        < !DOCTYPE html >
            <html>
                <head>
                    <title>Reporte de Cierre - ${data.nombre_agencia}</title>
                    <style>
                        body {font - family: 'Segoe UI', Arial, sans-serif; font-size: 12px; margin: 40px; color: #333; }
                        .header {text - align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
                        h1 {margin: 0; font-size: 20px; text-transform: uppercase; color: #000; }
                        h2 {margin: 5px 0 0; font-size: 14px; font-weight: normal; color: #666; }
                        .info-grid {display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 5px; }
                        .info-item label {display: block; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #888; margin-bottom: 3px; }
                        .info-item span {font - size: 14px; font-weight: 600; }
                        .boveda-card {border: 2px solid #000; padding: 15px; width: fit-content; margin-bottom: 30px; background: #fff; }
                        table {width: 100%; border-collapse: collapse; margin-bottom: 40px; }
                        th {background: #eee; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; }
                        td {padding: 8px; border-bottom: 1px solid #eee; }
                        .text-right {text - align: right; }
                        .text-center {text - align: center; }
                        .total-row td {border - top: 2px solid #000; font-weight: bold; font-size: 14px; padding-top: 10px; }
                        .signatures {margin - top: 100px; display: flex; justify-content: space-between; padding: 0 50px; }
                        .sig-box {width: 250px; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
                        .sig-name {font - weight: bold; margin-bottom: 3px; text-transform: uppercase; }
                        .sig-role {font - size: 11px; color: #666; }
                    </style>
                </head>
                <body onload="window.print();">
                    <div class="header">
                        <h1>${data.nombre_agencia}</h1>
                        <h2>Reporte de Cierre de Agencia | ${data.fecha}</h2>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Oficial Responsable</label>
                            <span>${data.nombre_oficial}</span>
                        </div>
                        <div class="info-item">
                            <label>Saldo en Bóveda</label>
                            <span style="font-size: 18px; color: #000;">L. ${parseFloat(data.saldo_boveda).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                        </div>
                    </div>

                    <h3 style="border-left: 4px solid #000; padding-left: 10px;">Detalle de Transacciones del Día</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Ref</th>
                                <th>Cliente</th>
                                <th>Cobrado Por</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filas}
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4" class="text-right">TOTAL RECAUDADO EN EL DÍA:</td>
                                <td class="text-right">L. ${totalRecaudado.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="signatures">
                        <div class="sig-box">
                            <div class="sig-name">${data.nombre_supervisor}</div>
                            <div class="sig-role">Supervisor de Agencia</div>
                        </div>
                        <div class="sig-box">
                            <div class="sig-name">${data.nombre_oficial}</div>
                            <div class="sig-role">Oficial de Operaciones</div>
                        </div>
                    </div>
                </body>
            </html>
    `;

    ventana.document.write(html);
    ventana.document.close();
}
