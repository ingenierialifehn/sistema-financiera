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
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const CLIENTE_ID = <?php echo $clienteId; ?>;
    </script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo BASE_URL; ?>/public/admin/clientes.php"
                        class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <i class="fas fa-id-card text-blue-600 mr-3"></i>Ficha del Cliente
                        </h1>
                        <p class="text-gray-600 mt-1" id="clienteNombre">Cargando...</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button onclick="editarCliente()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition shadow-lg">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </button>
                    <button onclick="imprimirFicha()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition shadow-lg">
                        <i class="fas fa-print mr-2"></i>Imprimir
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
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-6">
                                <div class="h-24 w-24 rounded-full border-4 border-white overflow-hidden bg-white">
                                    ${cliente.foto_perfil
                    ? `<img src="${BASE_URL}/uploads/documentos/${cliente.foto_perfil}" class="h-full w-full object-cover" alt="${cliente.nombre_completo}">`
                    : `<div class="h-full w-full flex items-center justify-center bg-blue-100">
                                            <i class="fas fa-user text-blue-600 text-4xl"></i>
                                           </div>`
                }
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold">${cliente.nombre_completo}</h2>
                                    <p class="text-blue-100 mt-1">Código: ${cliente.codigo_cliente || 'N/A'}</p>
                                    <p class="text-blue-100">DNI: ${cliente.numero_documento}</p>
                                </div>
                            </div>
                            <div>
                                ${estadoBadge}
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <!-- Datos Personales -->
                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Tipo de Documento</label>
                                    <p class="text-gray-900 font-semibold">${cliente.tipo_documento}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Número de Documento</label>
                                    <p class="text-gray-900 font-semibold">${cliente.numero_documento}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Fecha de Nacimiento</label>
                                    <p class="text-gray-900 font-semibold">${cliente.fecha_nacimiento ? new Date(cliente.fecha_nacimiento).toLocaleDateString('es-HN') : 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Género</label>
                                    <p class="text-gray-900 font-semibold">${cliente.genero === 'M' ? 'Masculino' : cliente.genero === 'F' ? 'Femenino' : 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Teléfono</label>
                                    <p class="text-gray-900 font-semibold">
                                        <i class="fas fa-phone text-green-600 mr-1"></i>${cliente.telefono || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-gray-900 font-semibold">
                                        ${cliente.email ? `<i class="fas fa-envelope text-blue-600 mr-1"></i>${cliente.email}` : 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Ocupación</label>
                                    <p class="text-gray-900 font-semibold">${cliente.ocupacion || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Agencia</label>
                                    <p class="text-gray-900 font-semibold">
                                        <i class="fas fa-building text-purple-600 mr-1"></i>${cliente.agencia_nombre || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación -->
                        <div class="mb-8 pt-8 border-t">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                                Ubicación
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <label class="text-sm font-medium text-gray-500">Dirección</label>
                                    <p class="text-gray-900 font-semibold">${cliente.direccion || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Departamento</label>
                                    <p class="text-gray-900 font-semibold">${cliente.departamento || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Municipio</label>
                                    <p class="text-gray-900 font-semibold">${cliente.municipio || 'N/A'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Barrio/Colonia</label>
                                    <p class="text-gray-900 font-semibold">${cliente.barrio || 'N/A'}</p>
                                </div>
                                ${cliente.gps_coordenadas ? `
                                <div class="md:col-span-3">
                                    <label class="text-sm font-medium text-gray-500">Coordenadas GPS</label>
                                    <p class="text-gray-900 font-semibold">
                                        <i class="fas fa-map-pin text-red-500 mr-1"></i>${cliente.gps_coordenadas}
                                        <a href="https://www.google.com/maps?q=${cliente.gps_coordenadas}" target="_blank" 
                                            class="ml-3 text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-external-link-alt mr-1"></i>Ver en Google Maps
                                        </a>
                                    </p>
                                </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Documentación -->
                        <div class="pt-8 border-t">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-file-image text-green-600 mr-2"></i>
                                Documentación
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                ${renderImageCard('DNI Frontal', cliente.foto_dni_frontal)}
                                ${renderImageCard('DNI Reverso', cliente.foto_dni_reverso)}
                                ${renderImageCard('Foto Perfil', cliente.foto_perfil)}
                                ${renderImageCard('Foto Casa', cliente.foto_casa)}
                                ${renderImageCard('Recibo', cliente.foto_recibo)}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Historial de Préstamos -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-history text-orange-600 mr-2"></i>
                            Historial de Préstamos
                        </h3>
                        <p class="text-gray-500 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i><br>
                            Sin préstamos registrados
                        </p>
                    </div>

                    <!-- Información del Sistema -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            Información del Sistema
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Fecha de Registro</label>
                                <p class="text-gray-900 font-semibold">
                                    ${cliente.created_at ? new Date(cliente.created_at).toLocaleString('es-HN') : 'N/A'}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Última Actualización</label>
                                <p class="text-gray-900 font-semibold">
                                    ${cliente.updated_at ? new Date(cliente.updated_at).toLocaleString('es-HN') : 'N/A'}
                                </p>
                            </div>
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
    </script>

    <style>
        @media print {
            .ml-64 {
                margin-left: 0 !important;
            }

            button {
                display: none !important;
            }
        }
    </style>
</body>

</html>