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
                    <button onclick="openNegocioModal()"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition shadow-lg ml-2">
                        <i class="fas fa-store mr-2"></i>+ Registrar Negocio
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
                                ${renderImageCard('DNI Posterior', cliente.foto_dni_posterior)}
                                ${renderImageCard('Foto Perfil', cliente.foto_perfil)}
                                ${renderImageCard('Fachada Casa', cliente.foto_fachada_casa)}
                                ${renderImageCard('Recibo Servicio', cliente.foto_recibo_servicio)}
                            </div>
                        </div>
                        
                        <!-- Negocios y Garantías -->
                        <div class="pt-8 border-t mt-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
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
    <script>
        // --- Gestión de Negocios ---

        function openNegocioModal() {
            $('#negocio_cliente_id').val(CLIENTE_ID);
            $('#modalNegocio').removeClass('hidden');
        }

        function closeNegocioModal() {
            $('#modalNegocio').addClass('hidden');
            $('#formNegocio')[0].reset();
            $('#garantias-list').html('<div class="text-center py-4 text-gray-400 italic text-sm" id="empty-garantias-msg">Presione "Agregar Garantía" para registrar bienes.</div>');
            garantiaCount = 0;
        }

        async function guardarNegocio(e) {
            e.preventDefault();
            if (!confirm('¿Está seguro de registrar este negocio?')) return;

            const formData = new FormData(e.target);

            try {
                // Adjust path to point the API correctly
                const response = await fetch(`${BASE_URL}/app/api/clientes/negocios/create.php`, {
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

        async function loadNegocios() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/clientes/negocios/list.php?cliente_id=${CLIENTE_ID}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(negocio => {
                        html += `
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
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
                                
                                <div class="ml-6 mb-4 bg-white p-3 rounded border border-gray-100">
                                    <h5 class="font-semibold text-gray-700 text-sm mb-1">Garantía Declarada</h5>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-bold text-gray-500">${negocio.garantia_descripcion || 'Sin especificar'}</span>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <span class="font-bold text-green-600">Valor Est: L ${negocio.garantia_valor || '0.00'}</span>
                                    </p>
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
                    <h4 class="font-bold text-yellow-800 mb-3 text-sm uppercase tracking-wide flex items-center">
                        <i class="fas fa-shield-alt mr-2"></i>Garantías Ofrecidas
                        <span class="ml-2 text-xs font-normal text-gray-500 lowercase">(Puede registrar hasta 3)</span>
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Garantía 1 -->
                        <div class="bg-white p-3 rounded border border-yellow-200 shadow-sm">
                            <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase">Garantía #1</h5>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                                <input type="text" name="garantia_descripcion_1" placeholder="Ej. Estufa Industrial"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                                <input type="number" step="0.01" name="garantia_valor_1" placeholder="0.00"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia (Foto)</label>
                                <input type="file" name="foto_garantia_1" accept="image/*" class="text-xs w-full">
                            </div>
                        </div>

                        <!-- Garantía 2 -->
                        <div class="bg-white p-3 rounded border border-yellow-200 shadow-sm">
                            <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase">Garantía #2</h5>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                                <input type="text" name="garantia_descripcion_2" placeholder="Ej. Refrigerador"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                                <input type="number" step="0.01" name="garantia_valor_2" placeholder="0.00"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia (Foto)</label>
                                <input type="file" name="foto_garantia_2" accept="image/*" class="text-xs w-full">
                            </div>
                        </div>

                        <!-- Garantía 3 -->
                        <div class="bg-white p-3 rounded border border-yellow-200 shadow-sm">
                            <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase">Garantía #3</h5>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                                <input type="text" name="garantia_descripcion_3" placeholder="Ej. Utensilios"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div class="mb-2">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                                <input type="number" step="0.01" name="garantia_valor_3" placeholder="0.00"
                                    class="text-xs w-full border rounded p-1">
                            </div>
                            <div>
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia (Foto)</label>
                                <input type="file" name="foto_garantia_3" accept="image/*" class="text-xs w-full">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h4 class="font-bold text-gray-800 mb-3 text-sm uppercase tracking-wide">Evidencias Fotográficas
                    </h4>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-xs font-bold mb-2">Documento Legal (Permiso Operaciones /
                            Factura Compra)</label>
                        <input type="file" name="doc_permiso_operaciones" accept="image/*,.pdf"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <label class="block text-gray-700 text-xs font-bold mb-2">Fotografías del Negocio (Máx 5)</label>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                        <input type="file" name="foto_negocio_1" accept="image/*" class="text-xs w-full">
                        <input type="file" name="foto_negocio_2" accept="image/*" class="text-xs w-full">
                        <input type="file" name="foto_negocio_3" accept="image/*" class="text-xs w-full">
                        <input type="file" name="foto_negocio_4" accept="image/*" class="text-xs w-full">
                        <input type="file" name="foto_negocio_5" accept="image/*" class="text-xs w-full">
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

    <script>
        // ... (existing functions) ...

        let garantiaCount = 0;

        function addGarantiaRow() {
            garantiaCount++;
            const id = garantiaCount;

            const html = `
                <div id="garantia-row-${id}" class="bg-white p-3 rounded border border-yellow-200 shadow-sm relative transition hover:shadow-md">
                    <button type="button" onclick="removeGarantiaRow(${id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <h5 class="text-xs font-bold text-yellow-700 mb-2 uppercase flex items-center">
                        <span class="bg-yellow-100 text-yellow-800 rounded-full w-5 h-5 flex items-center justify-center text-xs mr-2">${id}</span>
                        Detalle de Garantía
                    </h5>
                    
                    <div class="grid grid-cols-12 gap-3 items-start">
                        <!-- Descripción -->
                        <div class="col-span-12 md:col-span-5">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Descripción</label>
                            <input type="text" name="garantias_descripcion[]" placeholder="Ej. Plancha Industrial 4 quemadores" required
                                class="text-sm w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-yellow-500 focus:outline-none">
                        </div>
                        
                        <!-- Valor -->
                        <div class="col-span-6 md:col-span-3">
                            <label class="block text-gray-600 text-xs font-bold mb-1">Valor (L)</label>
                            <input type="number" step="0.01" name="garantias_valor[]" placeholder="0.00" required
                                class="text-sm w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
                        </div>

                        <!-- Foto y Preview -->
                        <div class="col-span-6 md:col-span-4 flex items-center space-x-2">
                             <div class="flex-grow">
                                <label class="block text-gray-600 text-xs font-bold mb-1">Evidencia</label>
                                <input type="file" name="garantias_fotos[]" accept="image/*" 
                                    class="text-xs w-full text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100"
                                    onchange="previewGarantiaImage(this, 'preview-${id}')">
                             </div>
                             <div class="flex-shrink-0 w-12 h-12 bg-gray-100 border rounded overflow-hidden flex items-center justify-center">
                                <img id="preview-${id}" src="" class="hidden w-full h-full object-cover">
                                <i id="preview-${id}-icon" class="fas fa-camera text-gray-300"></i>
                             </div>
                        </div>
                    </div>
                </div>
            `;

            $('#garantias-list').append(html);
        }

        function removeGarantiaRow(id) {
            $(`#garantia-row-${id}`).remove();
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
    }

    button {
    display: none !important;
    }
    }
    </style>
</body>

</html>