<?php
/**
 * Ficha del Cliente
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();

$clienteId = $_GET['id'] ?? null;
if (!$clienteId) {
    header('Location: ' . BASE_URL . '/public/admin/clientes.php');
    exit;
}

$pageTitle = 'Ficha del Cliente';
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

        const CLIENTE_ID = <?php echo $clienteId; ?>;
        const USER_PERMISSIONS = {
            edit_business: <?php echo Auth::hasPermission('clientes.edit_business') ? 'true' : 'false'; ?>,
            delete_business: <?php echo Auth::hasPermission('clientes.delete_business') ? 'true' : 'false'; ?>
        };
        console.log('BASE_URL:', BASE_URL); // Debug
    </script>
</head>

<body class="bg-gray-50 pb-12">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Header -->
        <div class="mb-6 lg:mb-8">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                <div class="flex items-center w-full lg:w-auto">
                    <a href="javascript:void(0)"
                        onclick="window.location.href = BASE_URL + '/public/admin/clientes.php'"
                        class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left text-xl lg:text-2xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                            <i class="fas fa-id-card text-blue-600 mr-2 lg:mr-3"></i>Ficha del Cliente
                        </h1>
                        <p class="text-gray-600 text-sm lg:text-base mt-1" id="clienteNombre">Cargando...</p>
                    </div>
                </div>

                <!-- Botones de Acción (Scroll horizontal en móvil) -->
                <div class="flex overflow-x-auto pb-2 w-full lg:w-auto gap-2 no-scrollbar">
                    <?php if (Auth::hasPermission('clientes.edit_business') || Auth::hasPermission('clientes.edit')): ?>
                        <button onclick="editarCliente()"
                            class="whitespace-nowrap bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition shadow text-xs md:text-sm font-medium">
                            <i class="fas fa-edit mr-1.5 md:mr-2"></i>Editar
                        </button>
                    <?php endif; ?>

                    <?php if (Auth::hasPermission('clientes.print_ficha')): ?>
                        <button onclick="imprimirFicha()"
                            class="hidden md:block whitespace-nowrap bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition shadow text-xs md:text-sm font-medium">
                            <i class="fas fa-print mr-1.5 md:mr-2"></i>Imprimir
                        </button>
                    <?php endif; ?>

                    <?php if (Auth::hasPermission('clientes.create_business')): ?>
                        <button onclick="openNegocioModal()"
                            class="whitespace-nowrap bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition shadow text-xs md:text-sm font-medium">
                            <i class="fas fa-store mr-1.5 md:mr-2"></i>Negocio
                        </button>
                    <?php endif; ?>

                    <button onclick="openPrestamoModal()"
                        class="whitespace-nowrap bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition shadow text-xs md:text-sm font-medium">
                        <i class="fas fa-money-bill-wave mr-1.5 md:mr-2"></i>Préstamo
                    </button>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div id="fichaContent" class="space-y-6">
            <!-- Loading -->
            <div class="flex justify-center items-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            loadClienteFicha();
        });

        function loadClienteFicha() {
            $.get(BASE_URL + '/app/api/clientes/get.php', { id: CLIENTE_ID }, function (response) {
                if (response.success) {
                    renderFicha(response.data);
                    loadNegocios();
                    loadPrestamos();
                } else {
                    Swal.fire('Error', 'No se pudo cargar la información del cliente', 'error')
                        .then(() => {
                            window.location.href = BASE_URL + '/public/admin/clientes.php';
                        });
                }
            }).fail(function () {
                Swal.fire('Error', 'Error de conexión', 'error')
                    .then(() => {
                        window.location.href = BASE_URL + '/public/admin/clientes.php';
                    });
            });
        }

        function renderFicha(cliente) {
            $('#clienteNombre').text(cliente.nombre_completo);

            const estadoBadge = cliente.estado === 'activo'
                ? '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Activo</span>'
                : '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>';

            const html = `
                <!-- Tarjeta Principal -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-8 md:px-8 md:py-6">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 text-center md:text-left w-full">
                                <div class="h-28 w-28 md:h-24 md:w-24 rounded-full border-4 border-white overflow-hidden bg-white shadow-md flex-shrink-0">
                                    ${cliente.foto_perfil
                    ? `<img src="${BASE_URL}/uploads/documentos/${cliente.foto_perfil}" class="h-full w-full object-cover" alt="${cliente.nombre_completo}">`
                    : `<div class="h-full w-full flex items-center justify-center bg-blue-100">
                                            <i class="fas fa-user text-blue-600 text-4xl"></i>
                                           </div>`
                }
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold leading-tight">${cliente.nombre_completo}</h2>
                                    <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-2">
                                        <span class="bg-white/20 px-2 py-0.5 rounded text-sm backdrop-blur-sm">
                                            Código: ${cliente.codigo_cliente || 'N/A'}
                                        </span>
                                        <span class="bg-white/20 px-2 py-0.5 rounded text-sm backdrop-blur-sm">
                                            DNI: ${cliente.numero_documento}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-row md:flex-col items-center gap-3 w-full md:w-auto justify-center md:justify-end border-t border-white/20 pt-4 md:border-0 md:pt-0">
                                ${estadoBadge}
                                ${getRiskBadge(cliente.categoria_riesgo, cliente.dias_mora_global)}
                            </div>
                        </div>
                    </div>

                    <div class="p-6 md:p-8">
                        <!-- Datos Personales -->
                        <div class="mb-8">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 flex items-center border-b pb-2">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-y-6 gap-x-4">
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Tipo de Documento</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.tipo_documento}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Número de Documento</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.numero_documento}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Fecha de Nacimiento</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.fecha_nacimiento ? new Date(cliente.fecha_nacimiento).toLocaleDateString('es-HN') : 'N/A'}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Género</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.genero === 'M' ? 'Masculino' : cliente.genero === 'F' ? 'Femenino' : 'N/A'}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Teléfono</label>
                                    <p class="text-gray-900 font-semibold mt-1 flex items-center">
                                        <i class="fas fa-phone text-green-600 mr-2"></i>
                                        <a href="tel:${cliente.telefono}" class="underline decoration-dotted">${cliente.telefono || 'N/A'}</a>
                                    </p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Email</label>
                                    <p class="text-gray-900 font-semibold mt-1 text-sm break-all">
                                        ${cliente.email ? `<i class="fas fa-envelope text-blue-600 mr-2"></i><a href="mailto:${cliente.email}" class="underline decoration-dotted">${cliente.email}</a>` : 'N/A'}
                                    </p>
                                </div>
                                 <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Ocupación</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.ocupacion || 'N/A'}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Agencia</label>
                                    <p class="text-gray-900 font-semibold mt-1">
                                        <i class="fas fa-building text-purple-600 mr-2"></i>${cliente.agencia_nombre || 'N/A'}
                                    </p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Registrado Por</label>
                                    <p class="text-gray-900 font-semibold mt-1">
                                        <i class="fas fa-user-edit text-orange-500 mr-2"></i>${cliente.creado_por_nombre || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación -->
                        <div class="mb-8 pt-8 md:border-t md:pt-8">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 flex items-center border-b pb-2">
                                <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                                Ubicación
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3 bg-gray-50 p-4 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Dirección Completa</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.direccion || 'N/A'}</p>
                                </div>
                                <div class="bg-white border md:border-0 p-3 rounded md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Departamento</label>
                                    <p class="text-gray-900 font-semibold">${cliente.departamento || 'N/A'}</p>
                                </div>
                                <div class="bg-white border md:border-0 p-3 rounded md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Municipio</label>
                                    <p class="text-gray-900 font-semibold">${cliente.municipio || 'N/A'}</p>
                                </div>
                                <div class="bg-white border md:border-0 p-3 rounded md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Barrio/Colonia</label>
                                    <p class="text-gray-900 font-semibold">${cliente.barrio || 'N/A'}</p>
                                </div>
                                <div class="md:col-span-3 bg-gray-50 p-4 rounded-lg md:bg-transparent md:p-0">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Punto de Referencia</label>
                                    <p class="text-gray-900 font-semibold mt-1">${cliente.punto_referencia || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Tipo de Vivienda</label>
                                    <p class="text-gray-900 font-semibold">${cliente.tipo_vivienda || 'N/A'}</p>
                                </div>
                                ${cliente.gps_coordenadas ? `
                                <div class="md:col-span-3 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <label class="text-xs font-bold text-blue-600 uppercase tracking-wide">Coordenadas GPS</label>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mt-1 gap-2">
                                        <p class="text-gray-900 font-mono font-medium">
                                            <i class="fas fa-satellite-dish text-blue-500 mr-2"></i>${cliente.gps_coordenadas}
                                        </p>
                                        <a href="https://www.google.com/maps?q=${cliente.gps_coordenadas}" target="_blank" 
                                            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded hover:bg-blue-700 transition shadow-sm w-full sm:w-auto">
                                            <i class="fas fa-map-marked-alt mr-2"></i>Abrir Mapa
                                        </a>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Documentación -->
                        <div class="pt-8 md:border-t md:pt-8">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 flex items-center border-b pb-2">
                                <i class="fas fa-file-image text-green-600 mr-2"></i>
                                Documentación
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                ${renderImageCard('DNI Frontal', cliente.foto_dni_frontal)}
                                ${renderImageCard('DNI Posterior', cliente.foto_dni_posterior)}
                                ${renderImageCard('Foto Perfil', cliente.foto_perfil)}
                                ${renderImageCard('Fachada Casa', cliente.foto_fachada_casa)}
                                ${renderImageCard('Recibo Servicio', cliente.foto_recibo_servicio)}
                            </div>
                        </div>
                        
                        <!-- Historial de Préstamos -->
                        <div class="pt-8 md:border-t md:pt-8 mt-8">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 flex items-center border-b pb-2">
                                <i class="fas fa-money-bill-wave text-orange-600 mr-2"></i>
                                Historial de Préstamos
                            </h3>
                            <div id="prestamos-container" class="space-y-4">
                                <div class="flex justify-center p-4">
                                    <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Negocios y Garantías -->
                        <div class="pt-8 md:border-t md:pt-8 mt-8">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 flex items-center border-b pb-2">
                                <i class="fas fa-briefcase text-yellow-600 mr-2"></i>
                                Negocios y Garantías
                            </h3>
                            <div id="negocios-container" class="space-y-4">
                                <div class="flex justify-center p-4">
                                    <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Información del Sistema
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between md:block border-b md:border-0 pb-2 md:pb-0">
                            <span class="text-gray-500 font-medium">Fecha de Registro:</span>
                            <span class="text-gray-900 font-semibold block md:inline md:ml-2">
                                ${cliente.created_at ? new Date(cliente.created_at).toLocaleString('es-HN') : 'N/A'}
                            </span>
                        </div>
                        <div class="flex justify-between md:block">
                            <span class="text-gray-500 font-medium">Última Actualización:</span>
                            <span class="text-gray-900 font-semibold block md:inline md:ml-2">
                                ${cliente.updated_at ? new Date(cliente.updated_at).toLocaleString('es-HN') : 'N/A'}
                            </span>
                        </div>
                    </div>
                </div>
            `;

            $('#fichaContent').html(html);
        }

        function renderImageCard(title, filename) {
            if (!filename) {
                return `
                    <div class="border border-gray-200 rounded-lg p-4 text-center">
                        <div class="h-32 flex items-center justify-center bg-gray-100 rounded mb-2">
                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                        </div>
                        <p class="text-xs text-gray-500">${title}</p>
                        <p class="text-xs text-red-500">No disponible</p>
                    </div>
                `;
            }

            return `
                <div class="border border-gray-200 rounded-lg p-4 text-center hover:shadow-lg transition cursor-pointer"
                    onclick="verImagen('${BASE_URL}/uploads/documentos/${filename}', '${title}')">
                    <div class="h-32 overflow-hidden rounded mb-2">
                        <img src="${BASE_URL}/uploads/documentos/${filename}" 
                            class="w-full h-full object-cover" 
                            alt="${title}">
                    </div>
                    <p class="text-xs text-gray-700 font-semibold">${title}</p>
                    <p class="text-xs text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>Disponible
                    </p>
                </div>
            `;
        }

        function verImagen(url, title) {
            Swal.fire({
                title: title,
                imageUrl: url,
                imageAlt: title,
                showCloseButton: true,
                showConfirmButton: false,
                width: '800px'
            });
        }

        function editarCliente() {
            window.location.href = `${BASE_URL}/public/admin/clientes.php?edit=${CLIENTE_ID}`;
        }

        function imprimirFicha() {
            window.print();
        }

        // --- Gestión de Préstamos ---

        async function loadPrestamos() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/clientes/prestamos/list.php?cliente_id=${CLIENTE_ID}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    renderPrestamos(data.data);
                } else {
                    $('#prestamos-container').html(`
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <i class="fas fa-money-bill-wave text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500 text-sm">No hay préstamos registrados para este cliente.</p>
                        </div>
                    `);
                }
            } catch (error) {
                console.error("Error loading préstamos", error);
                $('#prestamos-container').html('<p class="text-red-500">Error al cargar préstamos.</p>');
            }
        }

        function renderPrestamos(prestamos) {
            let html = '';

            prestamos.forEach((p, index) => {
                const estadoClass = {
                    'Activo': 'bg-green-100 text-green-800',
                    'Finalizado': 'bg-gray-100 text-gray-800',
                    'Solicitado': 'bg-blue-100 text-blue-800',
                    'En Análisis': 'bg-yellow-100 text-yellow-800',
                    'Aprobado': 'bg-purple-100 text-purple-800',
                    'Rechazado': 'bg-red-100 text-red-800',
                    'Listo para Entrega': 'bg-indigo-100 text-indigo-800'
                }[p.estado] || 'bg-gray-100 text-gray-800';

                const moraClass = p.dias_mora > 0 ? 'text-red-600' : 'text-green-600';
                const moraIcon = p.dias_mora > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle';

                html += `
                    <div class="bg-white border border-gray-200 rounded-lg p-5 hover:shadow-lg transition">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-bold text-lg text-gray-800">
                                        Préstamo #${p.id}
                                    </h4>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                                        ${p.estado}
                                    </span>
                                    ${p.dias_mora > 0 ? `
                                        <span class="px-2 py-1 text-xs font-bold rounded bg-red-100 text-red-700">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>${p.dias_mora} días en mora
                                        </span>
                                    ` : ''}
                                </div>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Solicitado: ${new Date(p.fecha_solicitud).toLocaleDateString('es-HN')}
                                    ${p.fecha_desembolso ? ` | Desembolsado: ${new Date(p.fecha_desembolso).toLocaleDateString('es-HN')}` : ''}
                                </p>
                            </div>
                            <button onclick="verDetallePrestamo(${p.id})" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-eye mr-2"></i>Ver Detalle
                            </button>
                        </div>

                        <!-- Grid de Información -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="bg-blue-50 p-3 rounded">
                                <p class="text-xs text-blue-600 font-semibold uppercase">Monto Capital</p>
                                <p class="text-lg font-bold text-blue-800">L ${p.monto_capital.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded">
                                <p class="text-xs text-green-600 font-semibold uppercase">Neto Entregado</p>
                                <p class="text-lg font-bold text-green-800">L ${p.neto_entregar.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded">
                                <p class="text-xs text-purple-600 font-semibold uppercase">Total a Pagar</p>
                                <p class="text-lg font-bold text-purple-800">L ${p.total_a_pagar.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div class="bg-orange-50 p-3 rounded">
                                <p class="text-xs text-orange-600 font-semibold uppercase">Balance Pendiente</p>
                                <p class="text-lg font-bold text-orange-800">L ${p.balance_pendiente.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                        </div>

                        <!-- Detalles del Préstamo -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm mb-4">
                            <div>
                                <p class="text-gray-500 text-xs">Modalidad</p>
                                <p class="font-semibold text-gray-800">${p.modalidad}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Plazo</p>
                                <p class="font-semibold text-gray-800">${p.plazo_meses} meses</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Tasa Total</p>
                                <p class="font-semibold text-gray-800">${p.tasa_total}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Valor Cuota</p>
                                <p class="font-semibold text-gray-800">L ${p.valor_cuota.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs">Capital Restante</p>
                                <p class="font-semibold text-gray-800">L ${p.capital_restante.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                            </div>
                        </div>

                        <!-- Progreso de Cuotas -->
                        <div class="mb-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Progreso de Cuotas</span>
                                <span class="font-semibold text-gray-800">${p.cuotas_pagadas} / ${p.total_cuotas} (${p.progreso}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all" 
                                    style="width: ${p.progreso}%"></div>
                            </div>
                        </div>

                        ${p.proxima_cuota_fecha ? `
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-3 rounded">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs font-semibold text-yellow-800 uppercase">Próxima Cuota</p>
                                        <p class="text-sm text-yellow-700">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            ${new Date(p.proxima_cuota_fecha).toLocaleDateString('es-HN')}
                                        </p>
                                    </div>
                                    <p class="text-lg font-bold text-yellow-800">
                                        L ${p.proxima_cuota_monto.toLocaleString('es-HN', { minimumFractionDigits: 2 })}
                                    </p>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            $('#prestamos-container').html(html);
        }

        function getRiskBadge(categoria, dias) {
            categoria = categoria || 'A';
            dias = dias || 0;

            let color = 'green';
            let label = 'Excelente';

            if (categoria === 'A') { color = 'green'; label = 'Excelente (A)'; }
            else if (categoria === 'B') { color = 'lime'; label = 'Bueno (B)'; }
            else if (categoria === 'C') { color = 'yellow'; label = 'Regular (C)'; }
            else if (categoria === 'D') { color = 'orange'; label = 'Riesgo Alto (D)'; }
            else { color = 'red'; label = 'Cobro Judicial (E)'; }

            return `
                <div class="px-3 py-1 bg-white bg-opacity-20 rounded-lg text-white text-xs font-bold border border-white border-opacity-30 text-right">
                    <div class="text-${color}-100 uppercase tracking-wider text-[10px]">Categoría</div>
                    <div class="text-sm">${label}</div>
                    <div class="text-[10px] opacity-75">${dias} días mora</div>
                </div>
            `;
        }

        function verDetallePrestamo(prestamoId) {
            window.location.href = `${BASE_URL}/public/admin/prestamo_detalle.php?id=${prestamoId}`;
        }
    </script>
    <script>
        // --- Gestión de Negocios ---

        function openNegocioModal() {
            // Clear previous form data to avoid mixing Edit/Create contexts
            $('#formNegocio')[0].reset();
            $('#negocio_cliente_id').val(CLIENTE_ID);
            $('#negocio_id').val(''); // IMPORTANT: Clear ID to signal "Create Mode"

            // Allow adding new guarantees from scratch
            $('#garantias-list').html('<div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">Presione "Agregar Garantía" para registrar bienes.</div>');
            garantiaCount = 0;

            // Reset previews
            ['preview-doc', 'preview-negocio-1', 'preview-negocio-2', 'preview-negocio-3', 'preview-negocio-4', 'preview-negocio-5'].forEach(id => {
                const img = document.getElementById(id);
                const icon = document.getElementById(id + '-icon');
                if (img) { img.src = ''; img.classList.add('hidden'); }
                if (icon) { icon.classList.remove('hidden'); }
            });

            $('#modalNegocio').removeClass('hidden');
        }

        function closeNegocioModal() {
            $('#modalNegocio').addClass('hidden');
            $('#formNegocio')[0].reset();
            $('#garantias-list').html('<div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">Presione "Agregar Garantía" para registrar bienes.</div>');
            garantiaCount = 0;

            // Reset additional previews
            ['preview-doc', 'preview-negocio-1', 'preview-negocio-2', 'preview-negocio-3', 'preview-negocio-4', 'preview-negocio-5'].forEach(id => {
                const img = document.getElementById(id);
                const icon = document.getElementById(id + '-icon');
                if (img) { img.src = ''; img.classList.add('hidden'); }
                if (icon) { icon.classList.remove('hidden'); }
            });
        }

        async function guardarNegocio(e) {
            e.preventDefault();
            if (!confirm('¿Está seguro de registrar este negocio?')) return;

            const formData = new FormData(e.target);
            const negocioId = $('#negocio_id').val();
            const url = negocioId ? `${BASE_URL}/app/api/clientes/negocios/update.php` : `${BASE_URL}/app/api/clientes/negocios/create.php`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire('Éxito', 'Negocio registrado correctamente', 'success');
                    closeNegocioModal();
                    loadNegocios();
                } else {
                    Swal.fire('Error', data.message || 'Error al registrar', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }

        let currentNegociosList = [];

        async function loadNegocios() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/clientes/negocios/list.php?cliente_id=${CLIENTE_ID}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    currentNegociosList = data.data; // Store for valid edit reference

                    // Calcular Gran Total
                    let granTotal = 0;
                    data.data.forEach(n => granTotal += parseFloat(n.total_garantias || 0));

                    // Barra de Resumen Global
                    let html = `
                        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 border-l-4 border-yellow-500 p-4 mb-6 rounded-lg shadow-sm flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="bg-yellow-200 rounded-full p-2 mr-3">
                                    <i class="fas fa-coins text-yellow-700 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-yellow-800 uppercase tracking-wide">Valor Total de Garantías</p>
                                    <p class="text-xs text-yellow-700">Consolidado de todos los negocios</p>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-green-700 bg-white px-5 py-2 rounded-lg shadow-sm border border-yellow-200">
                                L ${granTotal.toLocaleString('es-HN', { minimumFractionDigits: 2 })}
                            </div>
                        </div>
                    `;

                    data.data.forEach((negocio, index) => {
                        html += `
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition relative">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-lg text-gray-800 flex items-center">
                                            <i class="fas fa-store mr-2 text-indigo-500"></i>${negocio.nombre_negocio}
                                        </h4>
                                        <p class="text-sm text-gray-600 ml-6"><span class="font-semibold">Rubro:</span> ${negocio.rubro}</p>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                        ${new Date(negocio.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                                
                                <div class="absolute top-12 right-4 flex space-x-3">
                                     ${USER_PERMISSIONS.edit_business ?
                                `<button onclick='editNegocio(${index})' class="text-blue-500 hover:text-blue-700 p-1 text-xl" title="Editar">
                                        <i class="fas fa-edit"></i>
                                     </button>` : ''}
                                     
                                     ${USER_PERMISSIONS.delete_business ?
                                `<button onclick="deleteNegocio(${negocio.id})" class="text-red-500 hover:text-red-700 p-1 text-xl" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                     </button>` : ''}
                                </div>
                                
                                <div class="ml-6">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">Evidencias:</p>
                                    <div class="flex flex-wrap gap-2">
                                         ${renderBusinessDoc(negocio.doc_permiso_operaciones, 'Doc. Legal')}
                                         ${renderBusinessImage(negocio.foto_negocio_1, 'Negocio 1')}
                                         ${renderBusinessImage(negocio.foto_negocio_2, 'Negocio 2')}
                                         ${renderBusinessImage(negocio.foto_negocio_3, 'Negocio 3')}
                                         ${renderBusinessImage(negocio.foto_negocio_4, 'Negocio 4')}
                                         ${renderBusinessImage(negocio.foto_negocio_5, 'Negocio 5')}
                                    </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-gray-100">
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="text-xs font-semibold text-yellow-700 uppercase tracking-wide">Garantías Registradas</p>
                                            <span class="text-sm font-bold text-green-700 bg-green-50 px-2 py-1 rounded">
                                                Total: L ${parseFloat(negocio.total_garantias || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}
                                            </span>
                                        </div>
                                        ${renderGarantiasList(negocio.garantias)}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#negocios-container').html(html);
                } else {
                    $('#negocios-container').html(`
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <i class="fas fa-briefcase text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500 text-sm">No hay negocios registrados para este cliente.</p>
                        </div>
                    `);
                }
            } catch (error) {
                console.error("Error loading negocios", error);
                $('#negocios-container').html('<p class="text-red-500">Error al cargar negocios.</p>');
            }
        }

        async function deleteNegocio(id) {
            if (!confirm('¿Está seguro de eliminar este negocio? Esta acción no se puede deshacer.')) return;

            try {
                const response = await fetch(`${BASE_URL}/app/api/clientes/negocios/delete.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('Eliminado', 'El negocio ha sido eliminado.', 'success');
                    loadNegocios();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Error al procesar la solicitud', 'error');
            }
        }

        function editNegocio(index) {
            try {
                const negocio = currentNegociosList[index];
                if (!negocio) {
                    console.error('Negocio no encontrado para el índice:', index);
                    Swal.fire('Error', 'No se pudo cargar la información del negocio.', 'error');
                    return;
                }

                // Reset form
                $('#formNegocio')[0].reset();
                $('#garantias-list').empty();
                garantiaCount = 0;

                // Populate fields
                $('#negocio_cliente_id').val(negocio.cliente_id);
                $('#negocio_id').val(negocio.id);
                $('input[name="nombre_negocio"]').val(negocio.nombre_negocio);
                $('input[name="rubro"]').val(negocio.rubro);

                // Populate Business Previews
                if (typeof setPreviewSource === 'function') {
                    setPreviewSource('preview-doc', negocio.doc_permiso_operaciones);
                    for (let i = 1; i <= 5; i++) {
                        setPreviewSource(`preview-negocio-${i}`, negocio[`foto_negocio_${i}`]);
                    }
                }

                // Populate Guarantees
                if (negocio.garantias && negocio.garantias.length > 0) {
                    $('#empty-garantias-msg').remove();
                    negocio.garantias.forEach(g => {
                        addGarantiaRowForEdit(g);
                    });
                } else {
                    $('#garantias-list').html('<div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">Presione "Agregar Garantía" para registrar bienes.</div>');
                }

                // Show modal
                $('#modalNegocio').removeClass('hidden');

            } catch (error) {
                console.error('Error en editNegocio:', error);
                Swal.fire('Error', 'Ocurrió un error al abrir el editor.', 'error');
            }
        }

        function addGarantiaRowForEdit(g) {
            garantiaCount++;
            const id = garantiaCount;
            // Similar to addGarantiaRow but pre-filled
            const html = `
                <div id="garantia-row-${id}" class="bg-white p-3 rounded border border-yellow-200 shadow-sm relative transition hover:shadow-md animate-fade-in-down">
                    <button type="button" onclick="removeGarantiaRow(${id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600 z-10" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase flex items-center">
                        <span class="bg-yellow-100 text-yellow-800 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 font-mono">${id}</span>
                        Detalle de Garantía
                    </h5>
                    
                    <div class="grid grid-cols-12 gap-3 items-start">
                <div class="col-span-12 md:col-span-5">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                            <input type="text" name="garantias_descripcion[]" value="${g.descripcion ? g.descripcion.replace(/"/g, '&quot;') : ''}" required
                                class="text-sm w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-yellow-500 focus:outline-none placeholder-gray-300">
                        </div>
                        
                        <div class="col-span-6 md:col-span-3">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">L</span>
                                <input type="number" step="0.01" name="garantias_valor[]" value="${g.valor}" required
                                    class="text-sm w-full border border-gray-300 rounded p-2 pl-6 focus:ring-2 focus:ring-green-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="col-span-6 md:col-span-4 flex items-center space-x-2">
                             <div class="flex-grow">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia</label>
                                <input type="hidden" name="garantias_existing_foto[]" value="${g.foto || ''}">
                                <label class="flex items-center justify-center w-full px-2 py-1 bg-white border border-yellow-300 rounded shadow-sm text-xs font-medium text-yellow-700 hover:bg-yellow-50 cursor-pointer transition">
                                    <i class="fas fa-camera mr-2"></i> Cambiar Foto
                                    <input type="file" name="garantias_fotos[]" accept="image/*" capture="environment" class="hidden"
                                        onchange="previewGarantiaImage(this, 'preview-${id}')">
                                </label>
                             </div>
                             
                             <div class="flex-shrink-0 w-12 h-12 bg-gray-100 border rounded overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-${id}" src="${g.foto ? BASE_URL + '/uploads/negocios/' + g.foto : ''}" class="${g.foto ? '' : 'hidden'} w-full h-full object-cover">
                                <i id="preview-${id}-icon" class="fas fa-image text-gray-300 text-lg ${g.foto ? 'hidden' : ''}"></i>
                             </div>
                        </div>
                    </div>
                </div>
            `;
            $('#garantias-list').append(html);
        }

        function renderBusinessImage(filename, title) {
            if (!filename) return '';
            return `
                <div class="w-16 h-16 border rounded overflow-hidden cursor-pointer hover:opacity-75 relative group"
                     onclick="verImagen('${BASE_URL}/uploads/negocios/${filename}', '${title}')" title="${title}">
                     <img src="${BASE_URL}/uploads/negocios/${filename}" class="w-full h-full object-cover">
                     <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition"></div>
                </div>
            `;
        }

        function renderGarantiasList(garantias) {
            if (!garantias || garantias.length === 0) return '<p class="text-xs text-gray-400 italic">Sin garantías registradas.</p>';

            let html = '<div class="space-y-2">';
            garantias.forEach((g, index) => {
                html += `
                    <div class="flex items-start text-sm bg-yellow-50 p-2 rounded border border-yellow-100">
                        <div class="flex-shrink-0 mr-3">
                            ${g.foto ?
                        `<img src="${BASE_URL}/uploads/negocios/${g.foto}" class="w-10 h-10 object-cover rounded shadow-sm cursor-pointer" onclick="verImagen('${BASE_URL}/uploads/negocios/${g.foto}', 'Garantía: ${g.descripcion}')">`
                        : '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-gray-400"><i class="fas fa-image"></i></div>'}
                        </div>
                        <div class="flex-grow">
                            <p class="font-semibold text-gray-800">${g.descripcion}</p>
                            <p class="text-green-600 font-bold text-xs">Valor: L ${parseFloat(g.valor).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            return html;
        }

        function renderBusinessDoc(filename, title) {
            if (!filename) return '';
            const ext = filename.split('.').pop().toLowerCase();
            if (ext === 'pdf') {
                return `
                    <div class="w-16 h-16 border rounded flex items-center justify-center bg-gray-100 cursor-pointer hover:bg-gray-200" 
                         onclick="window.open('${BASE_URL}/uploads/negocios/${filename}', '_blank')" title="${title}">
                         <i class="fas fa-file-pdf text-red-600 text-2xl"></i>
                    </div>
                `;
            }
            return renderBusinessImage(filename, title);
        }

        let garantiaCount = 0;

        function addGarantiaRow() {
            garantiaCount++;
            const id = garantiaCount;

            // Remove empty message if exists
            $('#empty-garantias-msg').remove();

            const html = `
                <div id="garantia-row-${id}" class="bg-white p-3 rounded border border-yellow-200 shadow-sm relative transition hover:shadow-md animate-fade-in-down">
                    <button type="button" onclick="removeGarantiaRow(${id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600 z-10" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase flex items-center">
                        <span class="bg-yellow-100 text-yellow-800 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2 font-mono">${id}</span>
                        Detalle de Garantía
                    </h5>
                    
                    <div class="grid grid-cols-12 gap-3 items-start">
                        <!-- Descripción -->
                        <div class="col-span-12 md:col-span-5">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                            <input type="text" name="garantias_descripcion[]" placeholder="Ej. Plancha Industrial 4 quemadores" required
                                class="text-sm w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-yellow-500 focus:outline-none placeholder-gray-300">
                        </div>
                        
                        <!-- Valor -->
                        <div class="col-span-6 md:col-span-3">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">L</span>
                                <input type="number" step="0.01" name="garantias_valor[]" placeholder="0.00" required
                                    class="text-sm w-full border border-gray-300 rounded p-2 pl-6 focus:ring-2 focus:ring-green-500 focus:outline-none">
                            </div>
                        </div>

                        <!-- Foto y Preview -->
                        <div class="col-span-6 md:col-span-4 flex items-center space-x-2">
                             <div class="flex-grow">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia</label>
                                <label class="flex items-center justify-center w-full px-2 py-1 bg-white border border-yellow-300 rounded shadow-sm text-xs font-medium text-yellow-700 hover:bg-yellow-50 cursor-pointer transition">
                                    <i class="fas fa-camera mr-2"></i> Subir Foto
                                    <input type="file" name="garantias_fotos[]" accept="image/*" capture="environment" class="hidden"
                                        onchange="previewGarantiaImage(this, 'preview-${id}')">
                                </label>
                             </div>
                             
                             <!-- Preview Container -->
                             <div class="flex-shrink-0 w-12 h-12 bg-gray-100 border rounded overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-${id}" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-${id}-icon" class="fas fa-image text-gray-300 text-lg"></i>
                                <div id="preview-${id}-overlay" class="absolute inset-0 bg-black bg-opacity-0 hidden group-hover:bg-opacity-10 transition"></div>
                             </div>
                        </div>
                    </div>
                </div>
            `;

            $('#garantias-list').append(html);
        }

        function removeGarantiaRow(id) {
            $(`#garantia-row-${id}`).remove();
            if ($('#garantias-list').children().length === 0) {
                $('#garantias-list').html('<div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">Presione "Agregar Garantía" para registrar bienes.</div>');
            }
        }

        function previewGarantiaImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const icon = document.getElementById(previewId + '-icon');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (icon) icon.classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                preview.classList.add('hidden');
                if (icon) icon.classList.remove('hidden');
            }
        }
    </script>

    <!-- Modal Registrar Negocio -->
    <div id="modalNegocio" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
        style="z-index: 100;">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-5xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-6 pb-2 border-b">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-store text-blue-600 mr-2"></i>Registrar Negocio
                </h3>
                <button onclick="closeNegocioModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="formNegocio" onsubmit="guardarNegocio(event)" enctype="multipart/form-data">
                <input type="hidden" name="cliente_id" id="negocio_cliente_id">
                <input type="hidden" name="negocio_id" id="negocio_id">

                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <h4 class="font-bold text-blue-800 mb-3 text-sm uppercase tracking-wide">Datos Principales</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nombre del Negocio <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="nombre_negocio" required placeholder="Ej. Abarrotería Doña Juana"
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Rubro / Actividad <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="rubro" required placeholder="Ej. Venta de comida"
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-yellow-800 text-sm uppercase tracking-wide flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i>Garantías Ofrecidas
                        </h4>
                        <button type="button" onclick="addGarantiaRow()"
                            class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-bold py-1 px-3 rounded transition shadow-sm border border-yellow-300">
                            <i class="fas fa-plus mr-1"></i>Agregar Garantía
                        </button>
                    </div>

                    <div id="garantias-list" class="space-y-3 max-h-60 overflow-y-auto pr-2">
                        <!-- Dynamic Rows Here -->
                        <div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">
                            Presione "Agregar Garantía" para registrar bienes.
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h4 class="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide">Evidencias Fotográficas
                    </h4>

                    <!-- Documento Legal -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-xs font-bold mb-2">Documento Legal (Permiso Operaciones /
                            Factura Compra)</label>
                        <div class="flex items-center space-x-4">
                            <div class="flex-grow">
                                <input type="file" name="doc_permiso_operaciones" accept="image/*" capture="environment"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    onchange="previewGarantiaImage(this, 'preview-doc')">
                            </div>
                            <div
                                class="flex-shrink-0 w-16 h-16 bg-gray-200 border rounded overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-doc" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-doc-icon" class="fas fa-file-invoice text-gray-400 text-xl"></i>
                                <!-- Optional: Link to open PDF if exists - hard without complex logic, so maybe just image preview for now -->
                            </div>
                        </div>
                    </div>

                    <!-- Fotos Negocio -->
                    <label class="block text-gray-700 text-xs font-bold mb-2">Fotografías del Negocio (Máx 5)</label>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <!-- Generar 5 bloques identicos pero con ID unicos -->
                        <div class="bg-white p-2 rounded border border-gray-200 shadow-sm flex flex-col items-center">
                            <div
                                class="w-full h-20 bg-gray-100 rounded mb-2 overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-negocio-1" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-negocio-1-icon" class="fas fa-camera text-gray-300"></i>
                            </div>
                            <label
                                class="cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs py-1 px-3 rounded font-bold w-full text-center transition">
                                <i class="fas fa-upload mr-1"></i> Subir
                                <input type="file" name="foto_negocio_1" accept="image/*" capture="environment"
                                    class="hidden" onchange="previewGarantiaImage(this, 'preview-negocio-1')">
                            </label>
                        </div>
                        <div class="bg-white p-2 rounded border border-gray-200 shadow-sm flex flex-col items-center">
                            <div
                                class="w-full h-20 bg-gray-100 rounded mb-2 overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-negocio-2" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-negocio-2-icon" class="fas fa-camera text-gray-300"></i>
                            </div>
                            <label
                                class="cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs py-1 px-3 rounded font-bold w-full text-center transition">
                                <i class="fas fa-upload mr-1"></i> Subir
                                <input type="file" name="foto_negocio_2" accept="image/*" capture="environment"
                                    class="hidden" onchange="previewGarantiaImage(this, 'preview-negocio-2')">
                            </label>
                        </div>
                        <div class="bg-white p-2 rounded border border-gray-200 shadow-sm flex flex-col items-center">
                            <div
                                class="w-full h-20 bg-gray-100 rounded mb-2 overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-negocio-3" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-negocio-3-icon" class="fas fa-camera text-gray-300"></i>
                            </div>
                            <label
                                class="cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs py-1 px-3 rounded font-bold w-full text-center transition">
                                <i class="fas fa-upload mr-1"></i> Subir
                                <input type="file" name="foto_negocio_3" accept="image/*" capture="environment"
                                    class="hidden" onchange="previewGarantiaImage(this, 'preview-negocio-3')">
                            </label>
                        </div>
                        <div class="bg-white p-2 rounded border border-gray-200 shadow-sm flex flex-col items-center">
                            <div
                                class="w-full h-20 bg-gray-100 rounded mb-2 overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-negocio-4" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-negocio-4-icon" class="fas fa-camera text-gray-300"></i>
                            </div>
                            <label
                                class="cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs py-1 px-3 rounded font-bold w-full text-center transition">
                                <i class="fas fa-upload mr-1"></i> Subir
                                <input type="file" name="foto_negocio_4" accept="image/*" capture="environment"
                                    class="hidden" onchange="previewGarantiaImage(this, 'preview-negocio-4')">
                            </label>
                        </div>
                        <div class="bg-white p-2 rounded border border-gray-200 shadow-sm flex flex-col items-center">
                            <div
                                class="w-full h-20 bg-gray-100 rounded mb-2 overflow-hidden flex items-center justify-center relative group">
                                <img id="preview-negocio-5" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-negocio-5-icon" class="fas fa-camera text-gray-300"></i>
                            </div>
                            <label
                                class="cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs py-1 px-3 rounded font-bold w-full text-center transition">
                                <i class="fas fa-upload mr-1"></i> Subir
                                <input type="file" name="foto_negocio_5" accept="image/*" capture="environment"
                                    class="hidden" onchange="previewGarantiaImage(this, 'preview-negocio-5')">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" onclick="closeNegocioModal()"
                        class="bg-gray-500 text-white font-bold py-2 px-6 rounded-lg mr-2 hover:bg-gray-600 transition">Cancelar</button>
                    <button type="submit"
                        class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition shadow-md">
                        <i class="fas fa-save mr-2"></i>Guardar Información
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nuevo Préstamo -->
    <div id="modalPrestamo" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
        style="z-index: 100;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                    <i class="fas fa-hand-holding-usd text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Nuevo Préstamo</h3>
                <div class="mt-2 px-7 py-3">
                    <form id="formPrestamo">
                        <input type="hidden" name="cliente_id" id="prestamo_cliente_id">
                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Monto Solicitado</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L</span>
                                <input type="number" name="monto" id="prestamo_monto" required min="1" step="0.01"
                                    class="shadow-sm border border-gray-300 rounded w-full py-2 pl-8 pr-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Plazo (Meses)</label>
                            <input type="number" name="plazo_meses" id="prestamo_plazo" required min="1" step="1"
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Ej. 12">
                        </div>

                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Modalidad de Pago</label>
                            <select name="modalidad" id="prestamo_modalidad" required
                                class="shadow-sm border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Seleccione...</option>
                                <option value="Diario">Diario</option>
                                <option value="Semanal">Semanal</option>
                                <option value="Catorcenal">Catorcenal</option>
                                <option value="Mensual">Mensual</option>
                            </select>
                        </div>

                        <!-- Resumen Calculado -->
                        <div id="calculo-info" class="mb-6 bg-blue-50 p-3 rounded text-sm hidden">
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Interés (11% Mensual):</span>
                                <span class="font-bold text-gray-800" id="calc-interes">L 0.00</span>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600">Total a Pagar:</span>
                                <span class="font-bold text-blue-700" id="calc-total">L 0.00</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-blue-200 mt-2">
                                <span class="text-gray-600 font-semibold">Cuota Estimada:</span>
                                <span class="font-bold text-green-700" id="calc-cuota">L 0.00</span>
                            </div>
                        </div>

                        <div class="items-center px-4 py-3">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-paper-plane mr-2"></i>Solicitar Crédito
                            </button>
                            <button type="button" onclick="closePrestamoModal()"
                                class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function openPrestamoModal() {
            // 1. Validar Préstamo Activo
            try {
                const response = await fetch(`${BASE_URL}/app/api/prestamos/check_active.php?cliente_id=${CLIENTE_ID}`);
                const data = await response.json();

                if (data.success) {
                    if (data.has_active_loan) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Acceso Denegado',
                            text: 'El cliente ya posee un préstamo activo. No se puede crear uno nuevo hasta que el actual finalice.',
                            confirmButtonColor: '#3b82f6'
                        });
                        return;
                    }

                    // Si no tiene activo, abrir modal
                    $('#modalPrestamo').removeClass('hidden');
                    $('#formPrestamo')[0].reset();
                    $('#prestamo_cliente_id').val(CLIENTE_ID);
                    $('#calculo-info').addClass('hidden');
                } else {
                    Swal.fire('Error', 'No se pudo verificar el estado del cliente', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Error de conexión al verificar préstamos', 'error');
            }
        }

        function closePrestamoModal() {
            $('#modalPrestamo').addClass('hidden');
        }

        // Live Calculation
        const inputs = ['#prestamo_monto', '#prestamo_plazo', '#prestamo_modalidad'];
        inputs.forEach(selector => {
            $(document).on('input change', selector, calculateLoan);
        });

        function calculateLoan() {
            const capital = parseFloat($('#prestamo_monto').val()) || 0;
            const meses = parseInt($('#prestamo_plazo').val()) || 0;

            if (capital > 0 && meses > 0) {
                // Tasa 11% Mensual
                // Total = Capital + (Capital * 0.11 * Meses)
                const tasaMensual = 0.11;
                const interesTotal = capital * tasaMensual * meses;
                const totalPagar = capital + interesTotal;

                $('#calc-interes').text(`L ${interesTotal.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                $('#calc-total').text(`L ${totalPagar.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);

                // Cuota Estimate
                const modalidad = $('#prestamo_modalidad').val();
                let numCuotas = 0;
                if (modalidad === 'Diario') numCuotas = meses * 20;
                else if (modalidad === 'Semanal') numCuotas = meses * 4;
                else if (modalidad === 'Catorcenal') numCuotas = meses * 2;
                else if (modalidad === 'Mensual') numCuotas = meses * 1;

                if (numCuotas > 0) {
                    const valorCuota = totalPagar / numCuotas;
                    $('#calc-cuota').text(`L ${valorCuota.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                } else {
                    $('#calc-cuota').text('Seleccione Modalidad');
                }

                $('#calculo-info').removeClass('hidden');
            } else {
                $('#calculo-info').addClass('hidden');
            }
        }

        $('#formPrestamo').on('submit', async function (e) {
            e.preventDefault();

            if (!confirm('¿Está seguro de solicitar este crédito? La solicitud quedará pendiente de aprobación.')) return;

            const formData = new FormData(this);

            try {
                const response = await fetch(`${BASE_URL}/app/api/prestamos/create.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Solicitud Registrada',
                        text: 'La solicitud de crédito ha sido enviada correctamente.',
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        window.location.reload();
                    });
                    closePrestamoModal();
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar la solicitud', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    </script>

    <script>
        // Duplicates removed to fix syntax error and enable functionality


        function setPreviewSource(previewId, filename) {
            const preview = document.getElementById(previewId);
            const icon = document.getElementById(previewId + '-icon');

            if (preview && filename) {
                const ext = filename.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                    preview.src = `${BASE_URL}/uploads/negocios/${filename}`;
                    preview.classList.remove('hidden');
                    if (icon) icon.classList.add('hidden');
                }
            } else {
                if (preview) {
                    preview.src = '';
                    preview.classList.add('hidden');
                }
                if (icon) icon.classList.remove('hidden');
            }
        }
    </script>
</body>

</html>