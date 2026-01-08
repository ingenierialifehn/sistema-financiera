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
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const PRESTAMO_ID = <?php echo $prestamoId; ?>;
    </script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="history.back()" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-2xl"></i>
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
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition shadow-lg">
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
                            <div class="bg-green-50 p-4 rounded-lg">
                                <p class="text-xs text-green-600 font-semibold uppercase mb-1">Neto Entregado</p>
                                <p class="text-2xl font-bold text-green-800">L ${parseFloat(prestamo.neto_entregar).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
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
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
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
                        </div>

                        <!-- Tasas Desglosadas -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
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
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border-2 border-green-200">
                            <h3 class="font-bold text-green-800 mb-4 flex items-center text-lg">
                                <i class="fas fa-check-circle mr-2"></i>
                                Montos Pagados (Desglose)
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <p class="text-xs text-blue-600 font-semibold uppercase mb-1">Capital Pagado</p>
                                    <p class="text-xl font-bold text-blue-800">L ${parseFloat(prestamo.capital_pagado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <p class="text-xs text-purple-600 font-semibold uppercase mb-1">Interés Pagado</p>
                                    <p class="text-xl font-bold text-purple-800">L ${parseFloat(prestamo.interes_pagado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <p class="text-xs text-orange-600 font-semibold uppercase mb-1">Gastos Pagados</p>
                                    <p class="text-xl font-bold text-orange-800">L ${parseFloat(prestamo.gastos_pagados || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <p class="text-xs text-pink-600 font-semibold uppercase mb-1">Comisión Pagada</p>
                                    <p class="text-xl font-bold text-pink-800">L ${parseFloat(prestamo.comision_pagada || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                </div>
                                <div class="bg-green-100 p-4 rounded-lg shadow-md border-2 border-green-300">
                                    <p class="text-xs text-green-700 font-semibold uppercase mb-1">Total Pagado</p>
                                    <p class="text-2xl font-bold text-green-900">L ${parseFloat(prestamo.total_pagado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Proceso de Solicitud y Mora -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Columna Izquierda: Timeline -->
                        <div>
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
                        <div class="flex items-center justify-center w-16 h-16 rounded-full ${bgColor} border-4 border-white shadow-lg z-10">
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

            let html = `
                <div class="overflow-x-auto">
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
                        <td class="px-4 py-3 text-sm text-gray-700">${new Date(c.fecha_vencimiento).toLocaleDateString('es-HN')}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">L ${parseFloat(c.monto_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-700">L ${parseFloat(c.capital_cuota || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-sm text-right text-purple-700">L ${parseFloat(c.interes_cuota || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-700">L ${parseFloat(c.gastos_cuota || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-sm text-right text-pink-700">L ${parseFloat(c.comision_cuota || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-700">L ${parseFloat(c.monto_pagado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                                ${c.estado}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            ${c.fecha_pago_real ? new Date(c.fecha_pago_real).toLocaleString('es-HN') : '-'}
                        </td>
                    </tr>
                `;
            });

            html += `
             </tbody>
                    </table>
                </div>
            `;

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
                if (diasMora <= 30) { colorClass = 'yellow'; icon = 'fa-exclamation-triangle'; }
                else if (diasMora <= 60) { colorClass = 'orange'; icon = 'fa-exclamation-circle'; }
                else { colorClass = 'red'; icon = 'fa-times-circle'; }
            }

            return `
                <div class="bg-${colorClass}-50 rounded-lg p-6 border border-${colorClass}-200 h-full">
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
                            <span class="text-xl font-bold text-red-600">L ${montoMora.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
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

        function imprimirDetalle() {
            window.print();
        }
    </script>
</body>

</html>