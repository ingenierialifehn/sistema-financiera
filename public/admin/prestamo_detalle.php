<?php
/**
 * Detalle Completo del Préstamo
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();

$prestamoId = $_GET['id'] ?? null;
if (!$prestamoId) {
    header('Location: ' . BASE_URL . '/public/admin/prestamos.php');
    exit;
}

$pageTitle = 'Detalle del Préstamo';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> - Sistema Financiero
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Configuración Híbrida de BASE_URL
        // PC (Localhost): Usa la configuración exacta de PHP para máxima estabilidad.
        // Móvil (IP): Calcula dinámicamente para evitar errores de conexión cruzada.
        const PHP_BASE_URL = '<?php echo BASE_URL; ?>';
        let BASE_URL = PHP_BASE_URL;

        if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;

            // Detectar base path buscando '/public'
            let publicIndex = pathname.indexOf('/public');
            if (publicIndex !== -1) {
                let basePath = pathname.substring(0, publicIndex);
                BASE_URL = protocol + '//' + host + basePath;
            } else {
                // Soporte para Virtual Hosts donde public es la raíz
                BASE_URL = protocol + '//' + host;
            }
        }

        const PRESTAMO_ID = <?php echo $prestamoId; ?>;
    </script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Header -->
        <div class="mb-6 lg:mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <button onclick="toggleSidebar()"
                        class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <button onclick="history.back()" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-xl lg:text-2xl"></i>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <i class="fas fa-file-invoice-dollar text-blue-600 mr-3"></i>Detalle del Préstamo
                        </h1>
                        <p class="text-gray-600 mt-1" id="prestamoInfo">Cargando...</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button onclick="imprimirDetalle()"
                        class="hidden md:inline-flex bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition shadow-lg items-center">
                        <i class="fas fa-print mr-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div id="detalleContent" class="space-y-6">
            <!-- Loading -->
            <div class="flex justify-center items-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>
        </div>
        <!-- Print Preview Modal -->
        <div id="printModal"
            class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-[90vh] flex flex-col">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-xl font-bold text-gray-800">Vista Previa de Impresión (Préstamo)</h3>
                    <button onclick="closePrintModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                <div class="flex-grow bg-gray-100 p-4 overflow-hidden">
                    <iframe id="printFrame" class="w-full h-full border shadow-sm bg-white"></iframe>
                </div>
                <div class="p-4 border-t flex justify-end gap-3 bg-gray-50">
                    <button onclick="closePrintModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 font-medium">
                        Cerrar
                    </button>
                    <button onclick="printFromFrame()"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold shadow flex items-center">
                        <i class="fas fa-print mr-2"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            loadPrestamoDetalle();
        });

        async function loadPrestamoDetalle() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/prestamos/get_detalle.php?id=${PRESTAMO_ID}`);
                const data = await response.json();

                if (data.success) {
                    renderDetalle(data.data);
                } else {
                    Swal.fire('Error', data.message || 'No se pudo cargar el préstamo', 'error')
                        .then(() => history.back());
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Error de conexión', 'error')
                    .then(() => history.back());
            }
        }

        function renderDetalle(data) {
            const prestamo = data.prestamo;
            const cuotas = data.cuotas;
            const comentarios = data.comentarios;

            // Store for reprint logic
            window.GLOBAL_CUOTAS = cuotas;

            // Calcular Cuotas Pagadas (Lógica Agrupada por ID de Cuota)
            let totalUnique = 0;
            let pagadasUnique = 0;
            if (cuotas && cuotas.length > 0) {
                const uniqueMap = {};
                cuotas.forEach(c => {
                    const num = c.numero_cuota;
                    if (!uniqueMap[num]) uniqueMap[num] = [];
                    uniqueMap[num].push(c.estado);
                });

                totalUnique = Object.keys(uniqueMap).length;
                // Una cuota cuenta como pagada solo si TODOS sus registros asociados están 'pagada'
                pagadasUnique = Object.values(uniqueMap).filter(estados =>
                    estados.every(e => e === 'pagada')
                ).length;
            }

            // Calcular Total de Cuotas Teórico (Plazo * Factor según Modalidad)
            // Lógica solicitada: Diario x20, Semanal x4, Catorcenal x2, Mensual x1
            let factor = 1; // Default Mensual
            const modalidad = (prestamo.modalidad || '').toLowerCase();

            if (modalidad.includes('diario')) factor = 20;
            else if (modalidad.includes('semanal')) factor = 4;
            else if (modalidad.includes('catorcenal')) factor = 2;

            const totalTeorico = Math.round((parseFloat(prestamo.plazo_meses) || 0) * factor);

            $('#prestamoInfo').text(`Préstamo #${prestamo.id} - ${prestamo.cliente_nombre}`);

            const estadoBadge = getEstadoBadge(prestamo.estado);

            const html = `
                <!-- Resumen del Préstamo -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Préstamo #${prestamo.id}</h2>
                                <p class="text-blue-100 mt-1">Cliente: ${prestamo.cliente_nombre}</p>
                                <p class="text-blue-100">DNI: ${prestamo.cliente_documento}</p>
                            </div>
                            <div>
                                ${estadoBadge}
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <!-- Grid de Información Principal -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-xs text-blue-600 font-semibold uppercase mb-1">Capital Pendiente</p>
                                <p class="text-2xl font-bold text-blue-800">L ${parseFloat(prestamo.capital_restante).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                <p class="text-xs text-blue-500 mt-1">Orig: L ${parseFloat(prestamo.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg">
                                <p class="text-xs text-red-600 font-semibold uppercase mb-1">Saldo Vencido</p>
                                <p class="text-2xl font-bold text-red-800">L ${parseFloat(prestamo.monto_mora_total).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-xs text-purple-600 font-semibold uppercase mb-1">Total a Pagar</p>
                                <p class="text-2xl font-bold text-purple-800">L ${parseFloat(prestamo.total_a_pagar).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <p class="text-xs text-orange-600 font-semibold uppercase mb-1">Balance Pendiente</p>
                                <p class="text-2xl font-bold text-orange-800">L ${parseFloat(prestamo.balance_pendiente).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                        </div>

                        <!-- Detalles del Préstamo -->
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 mb-6">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Modalidad</label>
                                <p class="text-gray-900 font-semibold">${prestamo.modalidad}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Plazo</label>
                                <p class="text-gray-900 font-semibold">${prestamo.plazo_meses} meses</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Tasa Total</label>
                                <p class="text-gray-900 font-semibold">${prestamo.tasa_total}%</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Valor Cuota</label>
                                <p class="text-gray-900 font-semibold">L ${parseFloat(prestamo.valor_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Cuotas Pagadas</label>
                                <p class="text-gray-900 font-semibold text-green-600">
                                    ${pagadasUnique}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Cant. Cuotas (Total)</label>
                                <p class="text-gray-900 font-semibold">
                                    ${totalTeorico}
                                </p>
                            </div>
                        </div>

    <!-- Tasas Desglosadas -->
    <div class="hidden md:block bg-gray-50 p-4 rounded-lg mb-6">
        <h3 class="font-bold text-gray-800 mb-3">Desglose de Tasas</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500">Tasa Interés</label>
                <p class="font-semibold text-gray-800">${prestamo.tasa_interes}%</p>
            </div>
            <div>
                <label class="text-xs text-gray-500">Tasa Gastos</label>
                <p class="font-semibold text-gray-800">${prestamo.tasa_gastos}%</p>
            </div>
            <div>
                <label class="text-xs text-gray-500">Tasa Comisión</label>
                <p class="font-semibold text-gray-800">${prestamo.tasa_comision}%</p>
            </div>
        </div>
    </div>

    <!-- Montos Pagados Desglosados -->
    <div class="hidden md:block bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border-2 border-green-200">
        <h3 class="font-bold text-green-800 mb-4 flex items-center text-lg">
            <i class="fas fa-check-circle mr-2"></i>
            Montos Pagados (Desglose)
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-xs text-blue-600 font-semibold uppercase mb-1">Capital Pagado</p>
                <p class="text-xl font-bold text-blue-800">L ${parseFloat(prestamo.capital_pagado ||
                0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-xs text-purple-600 font-semibold uppercase mb-1">Interés Pagado</p>
                <p class="text-xl font-bold text-purple-800">L ${parseFloat(prestamo.interes_pagado ||
                    0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-xs text-orange-600 font-semibold uppercase mb-1">Gastos Pagados</p>
                <p class="text-xl font-bold text-orange-800">L ${parseFloat(prestamo.gastos_pagados ||
                        0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <p class="text-xs text-pink-600 font-semibold uppercase mb-1">Comisión Pagada</p>
                <p class="text-xl font-bold text-pink-800">L ${parseFloat(prestamo.comision_pagada ||
                            0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
            </div>
            <div class="bg-green-100 p-4 rounded-lg shadow-md border-2 border-green-300">
                <p class="text-xs text-green-700 font-semibold uppercase mb-1">Total Pagado</p>
                <p class="text-2xl font-bold text-green-900">L ${parseFloat(prestamo.total_pagado ||
                                0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Proceso de Solicitud y Mora -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Columna Izquierda: Timeline -->
            <div class="hidden md:block">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-route text-indigo-600 mr-2"></i>
                    Proceso de Solicitud
                </h3>
                ${renderProceso(prestamo)}
            </div>

            <!-- Columna Derecha: Estado de Mora -->
            <div>
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                    Estado del Crédito / Mora
                </h3>
                ${renderEstadoMora(prestamo)}
            </div>
        </div>
    </div>

    <!-- Comentarios y Análisis -->
    ${comentarios && comentarios.length > 0 ? `
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-comments text-yellow-600 mr-2"></i>
            Comentarios y Análisis
        </h3>
        ${renderComentarios(comentarios, prestamo)}
    </div>
    ` : ''}

    <!-- Calendario de Cuotas -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-calendar-alt text-green-600 mr-2"></i>
            Calendario de Cuotas
        </h3>
        ${renderCuotas(cuotas)}
    </div>
    `;

            $('#detalleContent').html(html);
        }

        function getEstadoBadge(estado) {
            const badges = {
                'Activo': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">Activo</span>',
                'Finalizado': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Finalizado</span>',
                'Solicitado': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Solicitado</span>',
                'En Análisis': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">En Análisis</span>',
                'Aprobado': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-purple-100 text-purple-800">Aprobado</span>',
                'Rechazado': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>',
                'Listo para Entrega': '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-indigo-100 text-indigo-800">Listo para Entrega</span>'
            };
            return badges[estado] || '<span class="px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">' + estado + '</span>';
        }

        function renderProceso(prestamo) {
            const steps = [
                {
                    title: 'Solicitud',
                    date: prestamo.fecha_solicitud,
                    user: prestamo.solicitante_nombre || 'N/A',
                    icon: 'fa-file-alt',
                    color: 'blue'
                },
                {
                    title: 'Análisis',
                    date: prestamo.fecha_analisis,
                    user: prestamo.analista_nombre || 'N/A',
                    icon: 'fa-search',
                    color: 'yellow'
                },
                {
                    title: 'Verificación',
                    date: prestamo.fecha_verificacion,
                    user: prestamo.verificador_nombre || 'N/A',
                    icon: 'fa-check-circle',
                    color: 'purple'
                },
                {
                    title: 'Desembolso',
                    date: prestamo.fecha_desembolso,
                    user: prestamo.oficial_desembolsos_nombre || 'N/A',
                    icon: 'fa-money-bill-wave',
                    color: 'green'
                }
            ];

            let html = '<div class="relative">';
            html += '<div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>';
            html += '<div class="space-y-6">';

            steps.forEach((step, index) => {
                const isCompleted = step.date != null;
                const bgColor = isCompleted ? `bg-${step.color}-100` : 'bg-gray-100';
                const textColor = isCompleted ? `text-${step.color}-800` : 'text-gray-400';
                const iconColor = isCompleted ? `text-${step.color}-600` : 'text-gray-400';

                html += `
            <div class="relative flex items-start">
                <div
                    class="flex items-center justify-center w-16 h-16 rounded-full ${bgColor} border-4 border-white shadow-lg z-10">
                    <i class="fas ${step.icon} text-2xl ${iconColor}"></i>
                </div>
                <div class="ml-6 flex-grow">
                    <h4 class="font-bold text-lg ${textColor}">${step.title}</h4>
                    ${isCompleted ? `
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar mr-1"></i>
                        ${new Date(step.date).toLocaleString('es-HN')}
                    </p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-user mr-1"></i>
                        ${step.user}
                    </p>
                    ` : `
                    <p class="text-sm text-gray-400 italic">Pendiente</p>
                    `}
                </div>
            </div>
            `;
            });
            html += '</div></div>';
            return html;
        }

        function renderComentarios(comentarios, prestamo) {
            let html = '<div class="space-y-4">';

            // Comentario de análisis
            if (prestamo.comentario_analisis) {
                html += `
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
            <div class="flex items-start">
                <i class="fas fa-comment-alt text-yellow-600 text-xl mr-3 mt-1"></i>
                <div class="flex-grow">
                    <h5 class="font-bold text-yellow-800 mb-1">Comentario del Analista</h5>
                    <p class="text-gray-700">${prestamo.comentario_analisis}</p>
                </div>
            </div>
        </div>
        `;
            }

            // Comentario de verificación
            if (prestamo.comentario_verificacion) {
                html += `
        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
            <div class="flex items-start">
                <i class="fas fa-comment-alt text-purple-600 text-xl mr-3 mt-1"></i>
                <div class="flex-grow">
                    <h5 class="font-bold text-purple-800 mb-1">Comentario de Verificación</h5>
                    <p class="text-gray-700">${prestamo.comentario_verificacion}</p>
                </div>
            </div>
        </div>
        `;
            }

            // Otros comentarios
            comentarios.forEach(c => {
                html += `
        <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded">
            <div class="flex items-start">
                <i class="fas fa-comment text-gray-600 text-xl mr-3 mt-1"></i>
                <div class="flex-grow">
                    <div class="flex justify-between items-start mb-2">
                        <h5 class="font-bold text-gray-800">${c.usuario_nombre || 'Usuario'}</h5>
                        <span class="text-xs text-gray-500">${new Date(c.created_at).toLocaleString('es-HN')}</span>
                    </div>
                    <p class="text-gray-700">${c.comentario}</p>
                </div>
            </div>
        </div>
        `;
            });

            html += '</div>';
            return html;
        }

        function renderCuotas(cuotas) {
            if (!cuotas || cuotas.length === 0) {
                return '<p class="text-gray-500 italic">No hay cuotas generadas para este préstamo.</p>';
            }

            // Vista Desktop (Tabla)
            let html = `
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Vencimiento</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Monto Cuota</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Capital</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Interés</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Gastos</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Comisión</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Pagado</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fecha Pago</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                `;

            cuotas.forEach(c => {
                const estadoClass = {
                    'pagada': 'bg-green-100 text-green-800',
                    'parcial': 'bg-yellow-100 text-yellow-800',
                    'pendiente': 'bg-gray-100 text-gray-600',
                    'en_mora': 'bg-red-100 text-red-800'
                }[c.estado] || 'bg-gray-100 text-gray-600';

                const rowClass = c.estado === 'pagada' ? 'bg-green-50' : c.estado === 'en_mora' ? 'bg-red-50' : '';

                html += `
                <tr class="${rowClass} hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">${c.numero_cuota}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${new
                        Date(c.fecha_vencimiento).toLocaleDateString('es-HN')}</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">L
                        ${parseFloat(c.monto_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-sm text-right text-blue-700">L ${parseFloat(c.capital_cuota ||
                            0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-sm text-right text-purple-700">L ${parseFloat(c.interes_cuota ||
                                0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-sm text-right text-orange-700">L ${parseFloat(c.gastos_cuota ||
                                    0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-sm text-right text-pink-700">L ${parseFloat(c.comision_cuota ||
                                        0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-green-700">L ${parseFloat(c.monto_pagado
                                            || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                            ${c.estado}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        ${c.fecha_pago_real ? new Date(c.fecha_pago_real).toLocaleString('es-HN') : '-'}
                    </td>
                    <td class="px-4 py-3 text-center">
                        ${c.monto_pagado > 0 && c.fecha_pago_real ? `
                        <button onclick="reimprimirRecibo('${c.fecha_pago_real}')" 
                            class="text-gray-600 hover:text-blue-600 transition" title="Reimprimir Recibo">
                            <i class="fas fa-print"></i>
                        </button>
                        ` : '-'}
                    </td>
                </tr>
                `;
            });

            html += `
            </tbody>
        </table>
    </div>

    <!-- Vista Móvil (Tarjetas) -->
    <div class="md:hidden space-y-4">
        `;

            cuotas.forEach(c => {
                const estadoClass = {
                    'pagada': 'bg-green-100 text-green-800',
                    'parcial': 'bg-yellow-100 text-yellow-800',
                    'pendiente': 'bg-gray-100 text-gray-600',
                    'en_mora': 'bg-red-100 text-red-800'
                }[c.estado] || 'bg-gray-100 text-gray-600';

                const borderClass = c.estado === 'en_mora' ? 'border-l-4 border-red-500' :
                    c.estado === 'pagada' ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300';

                html += `
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100 ${borderClass}">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase">Cuota #${c.numero_cuota}</span>
                    <div class="text-lg font-bold text-gray-900">L ${parseFloat(c.monto_cuota).toLocaleString('es-HN', {
                    minimumFractionDigits: 2
                })}</div>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                    ${c.estado}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                <div>
                    <span class="block text-xs text-gray-400">Vencimiento</span>
                    ${new Date(c.fecha_vencimiento).toLocaleDateString('es-HN')}
                </div>
                <div>
                    <span class="block text-xs text-gray-400">Fecha Pago</span>
                    ${c.fecha_pago_real ? new Date(c.fecha_pago_real).toLocaleDateString('es-HN') : '-'}
                </div>
            </div>

            <div class="space-y-1 bg-gray-50 p-2 rounded text-xs">
                <div class="flex justify-between">
                    <span>Capital:</span>
                    <span class="font-medium text-blue-700">L ${parseFloat(c.capital_cuota || 0).toLocaleString('es-HN',
                    { minimumFractionDigits: 2 })}</span>
                </div>
                <div class="flex justify-between">
                    <span>Interés:</span>
                    <span class="font-medium text-purple-700">L ${parseFloat(c.interes_cuota ||
                        0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-1 mt-1">
                    <span class="font-bold">Pagado:</span>
                    <span class="font-bold text-green-700">L ${parseFloat(c.monto_pagado || 0).toLocaleString('es-HN', {
                            minimumFractionDigits: 2
                        })}</span>
                </div>
            </div>
            
            ${c.monto_pagado > 0 && c.fecha_pago_real ? `
            <div class="mt-3 flex justify-end">
                 <button onclick="reimprimirRecibo('${c.fecha_pago_real}')" 
                     class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1 px-3 rounded border border-gray-300 shadow-sm flex items-center">
                     <i class="fas fa-print mr-1"></i> Reimprimir
                 </button>
            </div>
            ` : ''}

        </div>
        `;
            });

            html += `
    </div>`;

            return html;
        }

        function renderEstadoMora(prestamo) {
            const diasMora = prestamo.dias_mora || 0;
            const cuotasVencidas = prestamo.cuotas_vencidas || 0;
            const montoMora = parseFloat(prestamo.monto_mora_total || 0);
            const estadoCredito = prestamo.estado_cliente_credito || 'Al día';

            let colorClass = 'green';
            let icon = 'fa-check-circle';

            if (diasMora > 0) {
                if (diasMora <= 30) { colorClass = 'yellow'; icon = 'fa-exclamation-triangle'; } else if (diasMora <= 60) {
                    colorClass = 'orange'; icon = 'fa-exclamation-circle';
                } else { colorClass = 'red'; icon = 'fa-times-circle'; }
            }
            return ` <div class="bg-${colorClass}-50 rounded-lg p-6 border border-${colorClass}-200 h-full">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0 bg-${colorClass}-100 rounded-full p-3 text-${colorClass}-600">
                <i class="fas ${icon} text-2xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-bold text-${colorClass}-800">Estado: ${estadoCredito}</h4>
                <p class="text-sm text-${colorClass}-700">Situación actual del cliente</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white bg-opacity-60 rounded p-3 flex justify-between items-center">
                <span class="text-gray-600 font-medium">Días en Mora:</span>
                <span class="text-xl font-bold text-gray-800">${diasMora} días</span>
            </div>

            <div class="bg-white bg-opacity-60 rounded p-3 flex justify-between items-center">
                <span class="text-gray-600 font-medium">Cuotas Vencidas:</span>
                <span class="text-xl font-bold text-gray-800">${cuotasVencidas} cuotas</span>
            </div>

            <div class="bg-white bg-opacity-60 rounded p-3 flex justify-between items-center">
                <span class="text-gray-600 font-medium">Monto Vencido:</span>
                <span class="text-xl font-bold text-red-600">L ${montoMora.toLocaleString('es-HN', {
                minimumFractionDigits: 2
            })}</span>
            </div>

            ${diasMora > 0 ? `
            <div class="mt-4 pt-4 border-t border-${colorClass}-200">
                <p class="text-xs text-${colorClass}-800 italic">
                    <i class="fas fa-info-circle mr-1"></i>
                    El cliente presenta atrasos. Se recomienda contactar para gestión de cobro.
                </p>
            </div>
            ` : `
            <div class="mt-4 pt-4 border-t border-green-200">
                <p class="text-xs text-green-800 italic">
                    <i class="fas fa-star mr-1"></i>
                    Cliente excelente. Sin atrasos registrados.
                </p>
            </div>
            `}
        </div>
        </div>
        `;
        }

        function reimprimirRecibo(fecha) {
            if (!fecha || !window.GLOBAL_CUOTAS) return;

            // Find all quotas paid at this EXACT timestamp
            const cuotasIds = window.GLOBAL_CUOTAS
                .filter(c => c.fecha_pago_real === fecha)
                .map(c => c.id);

            if (cuotasIds.length === 0) {
                Swal.fire('Error', 'No se encontraron registros para esta fecha', 'error');
                return;
            }

            const idsParam = cuotasIds.join(',');
            const url = `${BASE_URL}/public/admin/print_docs.php?type=ticket_pago&ids=${idsParam}`;

            // Open popup
            window.open(url, 'Ticket', 'width=450,height=600,scrollbars=yes');
        }

        function imprimirDetalle() {
            const url = BASE_URL + '/public/admin/print_estado_cuenta.php?prestamo_id=' + PRESTAMO_ID;
            $('#printFrame').attr('src', url);
            $('#printModal').removeClass('hidden').addClass('flex');
        }

        function closePrintModal() {
            $('#printModal').addClass('hidden').removeClass('flex');
            $('#printFrame').attr('src', '');
        }

        function printFromFrame() {
            const iframe = document.getElementById('printFrame');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        }
    </script>
</body>

</html>