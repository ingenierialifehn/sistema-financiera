<?php
/**
 * Análisis de Préstamos
 */
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();
$pageTitle = 'Análisis de Préstamos';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Préstamos - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-search-dollar text-blue-600 mr-3"></i>Análisis de Préstamos
                </h1>
                <p class="text-gray-600 mt-1">Bandeja de entrada de solicitudes de crédito.</p>
            </div>
            <div class="flex gap-2 items-center">
                <label
                    class="flex items-center bg-white border border-gray-300 px-4 py-2 rounded-lg cursor-pointer hover:bg-gray-50 transition h-10">
                    <input type="checkbox" id="filtro_en_ruta" onchange="loadSolicitudes()"
                        class="mr-2 w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                    <i class="fas fa-route text-orange-600 mr-2"></i>
                    <span class="text-gray-700 font-medium">Solo en Ruta</span>
                </label>
                <select id="filtro_agencia" onchange="loadSolicitudes()"
                    class="bg-white border text-gray-700 px-4 py-2 rounded-lg focus:outline-none shadow-sm h-10">
                    <option value="">Todas las Agencias</option>
                </select>
                <button onclick="loadSolicitudes()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition h-10">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
        </div>

        <!-- Tabla de Solicitudes -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Agencia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Modalidad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaSolicitudes" class="bg-white divide-y divide-gray-200">
                        <!-- Dynamic Rows -->
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">Cargando solicitudes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Gestionar Estado -->
    <div id="modalGestion" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
        style="z-index: 50;">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4 pb-2 border-b">
                <h3 class="text-lg font-bold text-gray-900">Gestionar Solicitud</h3>
                <button onclick="closeModalGestion()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formGestion">
                <input type="hidden" id="gestion_prestamo_id" name="prestamo_id">

                <div class="mb-4">
                    <div class="flex justify-between">
                        <p class="text-sm text-gray-600 mb-2">Cliente: <span id="gestion_cliente"
                                class="font-bold text-gray-800"></span></p>
                        <p class="text-sm text-gray-600 mb-2">
                            <span id="gestion_tipo"
                                class="font-bold px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs"></span>
                            <span id="gestion_riesgo_container" class="ml-2"></span>
                        </p>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Estado Actual: <span id="gestion_estado_actual"
                            class="font-bold text-blue-600"></span></p>

                    <select id="gestion_nuevo_estado" name="nuevo_estado"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-4">
                        <option value="">Seleccione...</option>
                        <option value="Solicitado">Solicitado</option>
                        <option value="En Análisis">En Análisis</option>
                        <option value="Verificación de Campo">Verificación de Campo</option>
                        <option value="Pendiente de Operaciones">Pendiente de Operaciones</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>

                    <div class="border-t pt-4 mt-4 mb-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Comentarios del Expediente</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Análisis de Crédito</label>
                                <textarea id="gestion_comentario_analisis" name="comentario_analisis" rows="3"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="Ingrese sus observaciones de análisis..."></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Verificación de Campo</label>
                                <textarea id="gestion_comentario_verificacion" name="comentario_verificacion" rows="3"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-50"
                                    placeholder="Reservado para verificador..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <h4 class="font-semibold text-gray-700 mb-2">Edición de Términos</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Monto Solicitado (L)</label>
                                <input type="number" step="0.01" id="gestion_monto" name="monto_capital"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Plazo (Meses)</label>
                                <input type="number" id="gestion_plazo" name="plazo_meses"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Modalidad</label>
                                <select id="gestion_modalidad" name="modalidad"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="Diario">Diario</option>
                                    <option value="Semanal">Semanal</option>
                                    <option value="Catorcenal">Catorcenal</option>
                                    <option value="Mensual">Mensual</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Tasa Total (%)</label>
                                <input type="number" step="0.01" id="gestion_tasa" name="tasa_total"
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Nº Cuotas Estimadas</label>
                                <input type="text" id="gestion_num_cuotas" readonly
                                    class="bg-gray-100 shadow border rounded w-full py-2 px-3 text-gray-600 leading-tight">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Valor Cuota</label>
                                <input type="text" id="gestion_cuota" readonly
                                    class="bg-gray-100 shadow border rounded w-full py-2 px-3 text-gray-600 leading-tight">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Total a Pagar</label>
                                <input type="text" id="gestion_total" readonly
                                    class="bg-gray-100 shadow border rounded w-full py-2 px-3 text-gray-600 font-semibold text-blue-600">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" onclick="closeModalGestion()"
                        class="mr-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Actualizar Estado</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            loadAgencias();
            loadSolicitudes();
        });

        async function loadAgencias() {
            try {
                const response = await fetch(BASE_URL + '/app/api/agencias/list.php');
                const result = await response.json();
                if (result.success) {
                    const select = $('#filtro_agencia');
                    result.data.forEach(agencia => {
                        select.append(`<option value="${agencia.id_agencia}">${agencia.nombre_agencia}</option>`);
                    });
                }
            } catch (e) {
                console.error("Error loading agencies", e);
            }
        }

        async function loadSolicitudes() {
            const agenciaId = $('#filtro_agencia').val();
            const enRuta = $('#filtro_en_ruta').is(':checked');

            let url = BASE_URL + '/app/api/prestamos/list_analysis.php';
            const params = [];

            if (agenciaId) {
                params.push(`agencia_id=${agenciaId}`);
            }

            if (enRuta) {
                params.push('en_ruta=1');
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderTable(data.data);
                } else {
                    $('#tablaSolicitudes').html(`<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error: ${data.message}</td></tr>`);
                }
            } catch (error) {
                console.error(error);
                $('#tablaSolicitudes').html('<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error de conexión</td></tr>');
            }
        }

        function renderTable(rows) {
            if (rows.length === 0) {
                $('#tablaSolicitudes').html('<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No hay solicitudes pendientes.</td></tr>');
                return;
            }

            let html = '';
            rows.forEach(row => {
                const fecha = new Date(row.fecha_solicitud).toLocaleDateString('es-HN');
                const monto = parseFloat(row.monto_capital).toLocaleString('es-HN', { style: 'currency', currency: 'HNL' });

                const tipo = row.tipo_prestamo || 'Nuevo';
                let tipoBadge = '';
                if (tipo === 'Refinanciamiento') tipoBadge = '<span class="px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800 border border-purple-200">Refinan.</span>';
                else if (tipo === 'Readecuacion') tipoBadge = '<span class="px-2 py-0.5 rounded text-xs bg-orange-100 text-orange-800 border border-orange-200">Readecuación</span>';
                else if (tipo === 'Represtamo') tipoBadge = '<span class="px-2 py-0.5 rounded text-xs bg-teal-100 text-teal-800 border border-teal-200">Représtamo</span>';
                else tipoBadge = '<span class="px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-600 border border-blue-100">Nuevo</span>';

                let badgeClass = 'bg-gray-100 text-gray-800';
                if (row.estado === 'Solicitado') badgeClass = 'bg-blue-100 text-blue-800';
                else if (row.estado === 'En Análisis') badgeClass = 'bg-yellow-100 text-yellow-800';
                else if (row.estado === 'Verificación de Campo') badgeClass = 'bg-purple-100 text-purple-800';
                else if (row.estado === 'Pendiente de Operaciones') badgeClass = 'bg-orange-100 text-orange-800';
                else if (row.estado === 'Aprobado') badgeClass = 'bg-green-100 text-green-800';

                html += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#${row.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${row.cliente_nombre}</div>
                            <div class="text-sm text-gray-500">${row.numero_documento}</div>
                            <!-- Risk Badge -->
                            ${getRiskBadge(row.categoria_riesgo, row.dias_mora_global)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${row.nombre_agencia || 'N/A'}</div>
                            <div class="text-xs text-gray-500 mt-1" title="Solicitado por">
                                <i class="fas fa-user-pen mr-1"></i>${row.creado_por || 'Sistema'}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">${monto}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.modalidad} (${row.plazo_meses} meses)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${fecha}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}">
                                ${row.estado}
                            </span>
                            <div class="mt-1">
                                ${tipoBadge}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="${BASE_URL}/public/admin/ficha_cliente.php?id=${row.id_cliente}"
                                class="text-blue-600 hover:text-blue-900 bg-blue-50 px-3 py-1 rounded transition mr-2" title="Ver Perfil Cliente">
                                <i class="fas fa-user-circle"></i>
                            </a>
                            <button onclick='openGestion(${JSON.stringify(row)})' 
                                class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded transition">
                                Ver Detalles / Gestionar
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#tablaSolicitudes').html(html);
        }

        // Global variables for calculation
        let currentMonto = 0;
        let currentPlazo = 0;
        let currentModalidad = '';

        function openGestion(row) {
            $('#gestion_prestamo_id').val(row.id);
            $('#gestion_cliente').text(row.cliente_nombre);
            $('#gestion_tipo').text(row.tipo_prestamo || 'Nuevo');
            $('#gestion_riesgo_container').html(getRiskBadge(row.categoria_riesgo, row.dias_mora_global));
            $('#gestion_estado_actual').text(row.estado);
            $('#gestion_nuevo_estado').val(row.estado); // Pre-select current state

            // Load financial data (Editable)
            $('#gestion_monto').val(row.monto_capital);
            $('#gestion_plazo').val(row.plazo_meses);
            $('#gestion_modalidad').val(row.modalidad);
            $('#gestion_tasa').val(parseFloat(row.tasa_total).toFixed(2));

            // Trigger calc to show initial values
            calculateTerms();

            // Disable editing Terms if Aprobado
            const isLocked = ['Aprobado', 'Rechazado'].includes(row.estado); // Simple client-side lock
            $('#gestion_tasa').prop('disabled', isLocked);
            $('#gestion_monto').prop('disabled', isLocked);
            $('#gestion_plazo').prop('disabled', isLocked);
            $('#gestion_modalidad').prop('disabled', isLocked);

            // Populate Comments
            $('#gestion_comentario_analisis').val(row.comentario_analisis || '');
            $('#gestion_comentario_verificacion').val(row.comentario_verificacion || '');

            // Logic for Editing Comments:
            // Analyst edits 'comentario_analisis'. 
            // Verifier edits 'comentario_verificacion'.
            // For now, let's assume if I'm on this page I might be an Analyst.
            // But if the loan is already in Verification, maybe I can't edit Analysis? 
            // User requested "Analista pueda editar...". Let's leave Analyst editable.

            // If we wanted to lock specific fields based on role/state, we'd do it here.
            // e.g. $('#gestion_comentario_verificacion').prop('readonly', true); // Analysts usually verify field data? Or Verifier does.

            $('#modalGestion').removeClass('hidden');
        }



        function closeModalGestion() {
            $('#modalGestion').addClass('hidden');
        }

        $('#gestion_monto, #gestion_plazo, #gestion_modalidad, #gestion_tasa').on('input change', calculateTerms);

        function calculateTerms() {
            const monto = parseFloat($('#gestion_monto').val()) || 0;
            const plazo = parseInt($('#gestion_plazo').val()) || 0;
            const modalidad = $('#gestion_modalidad').val();
            const tasa = parseFloat($('#gestion_tasa').val()) || 0;

            if (tasa > 0 && monto > 0 && plazo > 0) {
                const totalInteres = monto * (tasa / 100) * plazo;
                const totalPagar = monto + totalInteres;

                let numCuotas = 0;
                if (modalidad === 'Diario') numCuotas = plazo * 20;
                else if (modalidad === 'Semanal') numCuotas = plazo * 4;
                else if (modalidad === 'Catorcenal') numCuotas = plazo * 2;
                else if (modalidad === 'Mensual') numCuotas = plazo * 1;

                const cuota = (numCuotas > 0) ? totalPagar / numCuotas : 0;

                $('#gestion_num_cuotas').val(numCuotas);
                $('#gestion_total').val('L ' + totalPagar.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#gestion_cuota').val('L ' + cuota.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
        }



        $('#formGestion').on('submit', async function (e) {
            e.preventDefault();

            // 1. Update Status
            const novoEstado = $('#gestion_nuevo_estado').val();
            // 2. Update Terms
            const nuevaTasa = $('#gestion_tasa').val();
            const nuevoMonto = $('#gestion_monto').val();
            const nuevoPlazo = $('#gestion_plazo').val();
            const nuevaModalidad = $('#gestion_modalidad').val();
            const comAnalisis = $('#gestion_comentario_analisis').val();
            const comVerif = $('#gestion_comentario_verificacion').val();

            try {
                // Step 1: Update Terms (including comments and core loan fields)
                const termsData = {
                    prestamo_id: $('#gestion_prestamo_id').val(),
                    tasa_total: nuevaTasa,
                    monto_capital: nuevoMonto,
                    plazo_meses: nuevoPlazo,
                    modalidad: nuevaModalidad,
                    comentario_analisis: comAnalisis,
                    comentario_verificacion: comVerif
                };

                const responseTerms = await fetch(BASE_URL + '/app/api/prestamos/update_terms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(termsData)
                });
                const resTerms = await responseTerms.json();

                if (!resTerms.success) {
                    Swal.fire('Error', 'Error al actualizar términos: ' + resTerms.message, 'error');
                    return;
                }

                // Step 2: Update Status
                if (novoEstado && novoEstado !== $('#gestion_estado_actual').text()) {
                    const statusData = {
                        prestamo_id: $('#gestion_prestamo_id').val(),
                        nuevo_estado: novoEstado
                    };
                    const responseStatus = await fetch(BASE_URL + '/app/api/prestamos/update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(statusData)
                    });
                    const resStatus = await responseStatus.json();

                    if (!resStatus.success) {
                        Swal.fire('Advertencia', 'Términos guardados, pero error al cambiar estado: ' + resStatus.message, 'warning');
                    } else {
                        Swal.fire('Éxito', 'Solicitud actualizada correctamente', 'success');
                    }
                } else {
                    Swal.fire('Éxito', 'Términos actualizados correctamente', 'success');
                }

                closeModalGestion();
                loadSolicitudes();

            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });

        function getRiskBadge(categoria, dias) {
            categoria = categoria || 'A';
            dias = dias || 0;

            let color = 'green';
            let label = 'A';

            if (categoria === 'A') { color = 'green'; label = 'A'; }
            else if (categoria === 'B') { color = 'yellow'; label = 'B'; }
            else if (categoria === 'C') { color = 'orange'; label = 'C'; }
            else if (categoria === 'D') { color = 'red'; label = 'D'; }
            else { color = 'red'; label = 'E'; }

            if (dias === 0 && categoria === 'A') return `
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">
                    Cat: A (0d)
                </span>
            `;

            return `
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-${color}-100 text-${color}-800 mt-1">
                    Cat: ${label} (${dias}d)
                </span>
            `;
        }
    </script>
</body>

</html>