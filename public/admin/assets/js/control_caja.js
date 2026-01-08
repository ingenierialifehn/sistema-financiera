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
    $('#jalarFondosModal').removeClass('hidden').addClass('flex');
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
        saldoReferencia = saldoBoveda;
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
    const diferencia = totalFisico - saldoBoveda;

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
                        Swal.fire('Éxito', 'Caja cerrada correctamente', 'success');
                        cerrarModalCierre();
                        cargarEstadoCaja();
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

function abrirModalCuadre() {
    // Reset Todo
    itemsCuadre = [];
    renderItemsCuadre();
    $('#cuadreAsesoresForm')[0].reset();

    $('#recaudadoHoyDisplay').text('L. 0.00');
    $('#entregadoHoyDisplay').text('L. 0.00');
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
                ops += `<option value="${a.id_usuario}">${a.nombre_completo}</option>`;
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
    if (asesor) {
        const monto = parseFloat(asesor.recaudado_hoy || 0);
        const entregado = parseFloat(asesor.entregado_hoy || 0);
        const pendiente = parseFloat(asesor.pendiente || 0);

        $('#recaudadoHoyDisplay').text('L. ' + monto.toLocaleString('es-HN', { minimumFractionDigits: 2 }));
        $('#entregadoHoyDisplay').text('L. ' + entregado.toLocaleString('es-HN', { minimumFractionDigits: 2 }));
        $('#pendienteDisplay').text('L. ' + pendiente.toLocaleString('es-HN', { minimumFractionDigits: 2 }));

        $('#infoRecaudadoContainer').removeClass('hidden');
    } else {
        $('#infoRecaudadoContainer').addClass('hidden');
    }
});

function actualizarDatosAsesor(asesorId) {
    if (!asesorId) return;
    $.get(BASE_URL + '/app/api/caja/get_asesores_recaudo.php', function (res) {
        if (res.success) {
            asesoresData = res.data;
            $('#asesorIdCuadre').trigger('change');
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

// --- GESTIÓN DE ITEMS ---

// Agregar Efectivo
$('#btnAgregarEfectivo').on('click', function () {
    const monto = parseFloat($('#montoEfectivoCuadre').val());
    if (!monto || monto <= 0) { Swal.fire('Error', 'Monto inválido', 'error'); return; }

    itemsCuadre.push({
        tipo: 'efectivo',
        monto: monto,
        detalle: 'Efectivo'
    });

    $('#montoEfectivoCuadre').val('');
    renderItemsCuadre();
});

// Agregar Deposito
$('#btnAgregarBanco').on('click', function () {
    const monto = parseFloat($('#montoBancoCuadre').val());
    const bancoId = $('#bancoIdCuadre').val();
    const bancoTexto = $('#bancoIdCuadre option:selected').text();
    const ref = $('#refBancoCuadre').val();

    if (!monto || monto <= 0) { Swal.fire('Error', 'Monto inválido', 'error'); return; }
    if (!bancoId) { Swal.fire('Error', 'Seleccione banco', 'error'); return; }
    if (!ref) { Swal.fire('Error', 'Ingrese referencia', 'error'); return; }

    itemsCuadre.push({
        tipo: 'banco',
        monto: monto,
        banco_id: bancoId,
        referencia: ref,
        detalle: `Banco: ${bancoTexto} (Ref: ${ref})`
    });

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
