<?php
/**
 * Módulo de Gestión de Clientes
 */

$pageTitle = 'Gestión de Clientes';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('clientes');

// Obtener información del usuario actual
$userAgenciaId = $_SESSION['id_agencia'] ?? $user['id_agencia'] ?? null;
$currentUser = $user ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Construir BASE_URL dinámicamente para compatibilidad móvil
        function getBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;
            let basePath = pathname.substring(0, pathname.indexOf('/public'));
            if (!basePath) {
                const projectIndex = pathname.indexOf('sistema-financiera');
                if (projectIndex !== -1) {
                    basePath = pathname.substring(0, projectIndex + 'sistema-financiera'.length);
                }
            }
            return protocol + '//' + host + basePath;
        }
        const BASE_URL = getBaseUrl();
        const USER_AGENCIA_ID = <?php echo $userAgenciaId ? $userAgenciaId : 'null'; ?>;
        console.log('BASE_URL:', BASE_URL);
        console.log('USER_AGENCIA_ID:', USER_AGENCIA_ID);
    </script>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-users text-blue-600 mr-3"></i>Gestión de Clientes
                    </h1>
                    <p class="text-gray-600 mt-2">Administra la información de tus clientes</p>
                </div>
                <button id="btnNuevoCliente"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl transition shadow-md hover:shadow-lg font-medium text-sm flex items-center gap-2 transform active:scale-95">
                    <i class="fas fa-user-plus text-blue-200"></i>Nuevo Cliente
                </button>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <!-- Filtros y Búsqueda -->
        <!-- Filtros y Búsqueda (Desktop) -->
        <div class="hidden lg:block bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                <div class="md:col-span-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">
                        <i class="fas fa-search mr-1.5 text-blue-500"></i>Buscar Cliente
                    </label>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Nombre, DNI o Código..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all">
                        <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">
                        <i class="fas fa-filter mr-1.5 text-blue-500"></i>Estado
                    </label>
                    <select id="filterEstado"
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white cursor-pointer hover:border-blue-300 transition-colors">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="inactivo">Inactivos</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">
                        <i class="fas fa-building mr-1.5 text-blue-500"></i>Agencia
                    </label>
                    <select id="filterAgencia"
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white cursor-pointer hover:border-blue-300 transition-colors">
                        <option value="">Todas las agencias</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda (Móvil) -->
        <div class="lg:hidden mb-6">
            <div class="flex gap-2">
                <div class="relative flex-grow">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    <input type="text" id="searchInputMobileDisplay" placeholder="Buscar cliente..." readonly
                        onclick="openMobileFilters()"
                        class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-xl shadow-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <button onclick="openMobileFilters()"
                    class="bg-white border border-gray-200 text-gray-700 px-4 py-3 rounded-xl shadow-sm hover:bg-gray-50 flex items-center justify-center min-w-[50px]">
                    <i class="fas fa-sliders-h text-lg text-blue-600"></i>
                </button>
            </div>
            <!-- Badges de filtros activos -->
            <div id="activeFiltersBadges" class="flex flex-wrap gap-2 mt-2 hidden">
                <!-- Se llenarán dinámicamente -->
            </div>
        </div>

        <!-- Vista de Tabla (PC) - Oculta en móvil -->
        <div id="vistaTabla" class="hidden lg:block bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                DNI</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Agencia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientesTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl"></i>
                                <p class="mt-2">Cargando clientes...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vista de Tarjetas (Móvil) - Oculta en PC -->
        <div id="vistaCards" class="lg:hidden space-y-4">
            <div id="clientesCardsContainer">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                    <p class="mt-3 text-gray-600">Cargando clientes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo/Editar Cliente -->
    <div id="modalCliente"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden">
            <!-- Header del Modal -->
            <div
                class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span id="modalTitle">Nuevo Cliente</span>
                </h3>
                <button id="btnCerrarModal" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="overflow-y-auto max-h-[calc(90vh-140px)]">
                <form id="formCliente" class="p-6" novalidate>
                    <input type="hidden" id="clienteId" name="id">

                    <!-- Pestañas -->
                    <div class="mb-6">
                        <div class="border-b border-gray-200">
                            <nav class="flex -mb-px space-x-8">
                                <button type="button"
                                    class="tab-button active py-4 px-1 border-b-2 border-blue-600 font-medium text-sm text-blue-600"
                                    data-tab="datos-personales">
                                    <i class="fas fa-user mr-2"></i>Datos Personales
                                </button>
                                <button type="button"
                                    class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    data-tab="ubicacion">
                                    <i class="fas fa-map-marker-alt mr-2"></i>Ubicación
                                </button>
                                <button type="button"
                                    class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                    data-tab="documentacion">
                                    <i class="fas fa-file-image mr-2"></i>Documentación
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab: Datos Personales -->
                    <div id="tab-datos-personales" class="tab-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nombre_completo" name="nombre_completo" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Ej: Juan Carlos Pérez García">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Documento <span class="text-red-500">*</span>
                                </label>
                                <select id="tipo_documento" name="tipo_documento" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="DNI">DNI</option>
                                    <option value="RUC">RUC</option>
                                    <option value="CE">Carnet de Extranjería</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de Documento <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="numero_documento" name="numero_documento" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Ej: 0801199012345">
                                <p id="dniError" class="text-red-500 text-xs mt-1 hidden">Este DNI ya está registrado
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha de Nacimiento
                                </label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Género
                                </label>
                                <select id="genero" name="genero"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Teléfono <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="telefono" name="telefono" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: 98765432">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" id="email" name="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="cliente@ejemplo.com">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Ocupación
                                </label>
                                <input type="text" id="ocupacion" name="ocupacion"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Comerciante">
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Ubicación -->
                    <div id="tab-ubicacion" class="tab-content hidden">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Dirección Completa
                                </label>
                                <textarea id="direccion" name="direccion" rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Col. Kennedy, Bloque A, Casa #15, Tegucigalpa"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Departamento
                                    </label>
                                    <input type="text" id="departamento" name="departamento"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Ej: Francisco Morazán">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Municipio
                                    </label>
                                    <input type="text" id="municipio" name="municipio"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Ej: Tegucigalpa">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Barrio/Colonia
                                    </label>
                                    <input type="text" id="barrio" name="barrio"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Ej: Col. Kennedy">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Punto de Referencia
                                </label>
                                <textarea id="punto_referencia" name="punto_referencia" rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Frente al parque central, al lado de la farmacia"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Vivienda
                                </label>
                                <select id="tipo_vivienda" name="tipo_vivienda"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="Propia">Propia</option>
                                    <option value="Alquilada">Alquilada</option>
                                    <option value="Familiar">Familiar</option>
                                    <option value="Pagándola">Pagándola</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                                    Coordenadas GPS
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" id="gps_coordenadas" name="gps_coordenadas" readonly
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                        placeholder="Latitud, Longitud">
                                    <button type="button" id="btnObtenerGPS"
                                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center">
                                        <i class="fas fa-crosshairs mr-2"></i>Obtener Ubicación
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Haz clic en el botón para obtener la ubicación actual del navegador
                                </p>
                            </div>

                            <div id="mapPreview" class="hidden">
                                <div class="bg-gray-100 rounded-lg p-4 border border-gray-300">
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        Ubicación capturada correctamente
                                    </p>
                                    <p class="text-xs text-gray-500" id="coordenadasDisplay"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Documentación -->
                    <div id="tab-documentacion" class="tab-content hidden">
                        <div class="space-y-6">
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                                <p class="text-sm text-blue-700">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Arrastra y suelta las imágenes o haz clic para seleccionarlas. Las imágenes se
                                    renombrarán automáticamente.
                                </p>
                            </div>

                            <!-- DNI Frontal -->
                            <div class="upload-zone" data-field="foto_dni_frontal">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-card mr-1"></i>DNI - Frontal <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer lg:hover:bg-blue-50 active:bg-blue-100">
                                    <input type="file" class="file-input hidden" accept="image/*" capture="environment"
                                        data-field="foto_dni_frontal">
                                    <div class="preview-container">
                                        <!-- Desktop View -->
                                        <div class="hidden lg:block">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar
                                            </p>
                                        </div>
                                        <!-- Mobile View -->
                                        <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                            <div
                                                class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                                <i class="fas fa-camera text-3xl text-blue-600"></i>
                                            </div>
                                            <p class="text-blue-700 font-bold text-lg">Tomar Foto DNI Frontal</p>
                                            <p class="text-sm text-gray-500">Toca para abrir cámara</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- DNI Reverso -->
                            <div class="upload-zone" data-field="foto_dni_posterior">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-id-card mr-1"></i>DNI - Reverso <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer lg:hover:bg-blue-50 active:bg-blue-100">
                                    <input type="file" class="file-input hidden" accept="image/*" capture="environment"
                                        data-field="foto_dni_posterior">
                                    <div class="preview-container">
                                        <!-- Desktop View -->
                                        <div class="hidden lg:block">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar
                                            </p>
                                        </div>
                                        <!-- Mobile View -->
                                        <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                            <div
                                                class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                                <i class="fas fa-camera text-3xl text-blue-600"></i>
                                            </div>
                                            <p class="text-blue-700 font-bold text-lg">Tomar Foto DNI Reverso</p>
                                            <p class="text-sm text-gray-500">Toca para abrir cámara</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Foto Perfil -->
                            <div class="upload-zone" data-field="foto_perfil">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-circle mr-1"></i>Foto de Perfil <span
                                        class="text-red-500">*</span>
                                </label>
                                <div
                                    class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer lg:hover:bg-blue-50 active:bg-blue-100">
                                    <input type="file" class="file-input hidden" accept="image/*" capture="environment"
                                        data-field="foto_perfil">
                                    <div class="preview-container">
                                        <!-- Desktop View -->
                                        <div class="hidden lg:block">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar
                                            </p>
                                        </div>
                                        <!-- Mobile View -->
                                        <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                            <div
                                                class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                                <i class="fas fa-camera text-3xl text-blue-600"></i>
                                            </div>
                                            <p class="text-blue-700 font-bold text-lg">Tomar Foto de Perfil</p>
                                            <p class="text-sm text-gray-500">Toca para abrir cámara</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Foto Casa -->
                            <div class="upload-zone" data-field="foto_fachada_casa">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-home mr-1"></i>Foto de Casa <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer lg:hover:bg-blue-50 active:bg-blue-100">
                                    <input type="file" class="file-input hidden" accept="image/*" capture="environment"
                                        data-field="foto_fachada_casa">
                                    <div class="preview-container">
                                        <!-- Desktop View -->
                                        <div class="hidden lg:block">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar
                                            </p>
                                        </div>
                                        <!-- Mobile View -->
                                        <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                            <div
                                                class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                                <i class="fas fa-camera text-3xl text-blue-600"></i>
                                            </div>
                                            <p class="text-blue-700 font-bold text-lg">Tomar Foto de Casa</p>
                                            <p class="text-sm text-gray-500">Toca para abrir cámara</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recibo Servicio -->
                            <div class="upload-zone" data-field="foto_recibo_servicio">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-file-invoice mr-1"></i>Recibo de Servicio <span
                                        class="text-red-500">*</span>
                                </label>
                                <div
                                    class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition cursor-pointer lg:hover:bg-blue-50 active:bg-blue-100">
                                    <input type="file" class="file-input hidden" accept="image/*" capture="environment"
                                        data-field="foto_recibo_servicio">
                                    <div class="preview-container">
                                        <!-- Desktop View -->
                                        <div class="hidden lg:block">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar
                                            </p>
                                        </div>
                                        <!-- Mobile View -->
                                        <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                            <div
                                                class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                                <i class="fas fa-camera text-3xl text-blue-600"></i>
                                            </div>
                                            <p class="text-blue-700 font-bold text-lg">Tomar Foto Recibo</p>
                                            <p class="text-sm text-gray-500">Toca para abrir cámara</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-8 flex justify-end gap-3 pt-6 border-t">
                        <button type="button" id="btnCancelar"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition shadow-md">
                            <i class="fas fa-save mr-2"></i>Guardar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Script de prueba inline para verificar que jQuery funciona
        console.log('jQuery loaded:', typeof $ !== 'undefined');
        console.log('BASE_URL:', BASE_URL);

        // Prueba simple del botón
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM loaded');
            const btnNuevo = document.getElementById('btnNuevoCliente');
            const modal = document.getElementById('modalCliente');
            console.log('Button found:', btnNuevo !== null);
            console.log('Modal found:', modal !== null);

            if (btnNuevo) {
                btnNuevo.addEventListener('click', function () {
                    console.log('Button clicked!');
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                });
            }
        });
    </script>
    <!-- Modal Filtros Móvil -->
    <div id="modalMobileFilters"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex-col justify-end lg:hidden">
        <div class="bg-white rounded-t-2xl w-full max-h-[90vh] overflow-y-auto animate-slide-up">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-filter text-blue-600 mr-2"></i>Filtros</h3>
                <button onclick="closeMobileFilters()"
                    class="text-gray-400 hover:text-gray-600 bg-white rounded-full p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Búsqueda -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        Búsqueda
                    </label>
                    <div class="relative">
                        <input type="text" id="searchInputMobile" placeholder="Nombre, DNI o Código..."
                            class="w-full pl-10 pr-4 py-3 text-base border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-gray-50">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        Estado del Cliente
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="setMobileFilterState('all')" id="btnStateAll"
                            class="mobile-filter-btn active py-2 px-3 rounded-lg text-sm font-medium border border-blue-600 bg-blue-50 text-blue-700 text-center transition-all">
                            Todos
                        </button>
                        <button type="button" onclick="setMobileFilterState('activo')" id="btnStateActivo"
                            class="mobile-filter-btn py-2 px-3 rounded-lg text-sm font-medium border border-gray-200 text-gray-600 text-center hover:bg-gray-50 transition-all">
                            Activo
                        </button>
                        <button type="button" onclick="setMobileFilterState('inactivo')" id="btnStateInactivo"
                            class="mobile-filter-btn py-2 px-3 rounded-lg text-sm font-medium border border-gray-200 text-gray-600 text-center hover:bg-gray-50 transition-all">
                            Inactivo
                        </button>
                        <input type="hidden" id="filterEstadoMobile" value="">
                    </div>
                </div>

                <!-- Agencia -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">
                        Agencia
                    </label>
                    <select id="filterAgenciaMobile"
                        class="w-full px-4 py-3 text-base border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white appearance-none">
                        <option value="">Todas las agencias</option>
                    </select>
                </div>
            </div>

            <div class="p-5 border-t border-gray-100 bg-gray-50 flex gap-3">
                <button onclick="resetMobileFilters()"
                    class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-100 transition">
                    Limpiar
                </button>
                <button onclick="applyMobileFilters()"
                    class="flex-[2] px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transform active:scale-95 transition">
                    Aplicar Filtros
                </button>
            </div>
        </div>
    </div>

    <style>
        .animate-slide-up {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }

            to {
                transform: translateY(0);
            }
        }
    </style>

    <script src="assets/js/clientes.js?v=<?php echo time(); ?>&force=1"></script>
</body>


</html>