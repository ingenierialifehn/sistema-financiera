/**
 * Gestión de Clientes - JavaScript
 * Incluye: Drag & Drop, Validación DNI, GPS, Tabs
 */

let uploadedFiles = {};
let isEditMode = false;

// Esperar a que el DOM y jQuery estén listos
document.addEventListener('DOMContentLoaded', function () {
    console.log('Clientes.js cargado');

    // Verificar que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }

    initializeModule();
});

function initializeModule() {
    loadClientes();
    loadAgencias();
    initializeTabs();
    initializeDragAndDrop();
    initializeEventHandlers();
    initializeGPS();
    initializeDNIValidation();

    // Check for edit parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        setTimeout(() => {
            editarCliente(editId);
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 500);
    }
}

// ============================================================================
// TABS
// ============================================================================

function initializeTabs() {
    $('.tab-button').on('click', function () {
        const tabId = $(this).data('tab');

        // Actualizar botones
        $('.tab-button').removeClass('active border-blue-600 text-blue-600')
            .addClass('border-transparent text-gray-500');
        $(this).addClass('active border-blue-600 text-blue-600')
            .removeClass('border-transparent text-gray-500');

        // Mostrar contenido
        $('.tab-content').addClass('hidden');
        $('#tab-' + tabId).removeClass('hidden');
    });
}

// ============================================================================
// DRAG AND DROP
// ============================================================================

function initializeDragAndDrop() {
    $('.dropzone').each(function () {
        const dropzone = $(this);
        const input = dropzone.find('.file-input');
        const field = input.data('field');

        // Click para abrir selector
        dropzone.on('click', function (e) {
            // Solo abrir el selector si NO se hizo clic en el botón de eliminar
            // y NO se hizo clic en el input mismo (para evitar bucle)
            if (!$(e.target).hasClass('btn-remove-image') && !$(e.target).is('input[type="file"]')) {
                input.click();
            }
        });

        // Cambio de archivo
        input.on('change', function (e) {
            if (e.target.files && e.target.files[0]) {
                handleFileSelect(e.target.files[0], field, dropzone);
            }
        });

        // Drag & Drop
        dropzone.on('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-blue-500 bg-blue-50');
        });

        dropzone.on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-blue-500 bg-blue-50');
        });

        dropzone.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-blue-500 bg-blue-50');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0], field, dropzone);
            }
        });
    });
}

function handleFileSelect(file, field, dropzone) {
    // Validar tipo
    if (!file.type.match('image.*')) {
        Swal.fire('Error', 'Solo se permiten imágenes', 'error');
        return;
    }

    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire('Error', 'La imagen no debe superar 5MB', 'error');
        return;
    }

    // Guardar archivo
    uploadedFiles[field] = file;

    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = `
            <div class="relative">
                <img src="${e.target.result}" class="max-h-48 mx-auto rounded-lg shadow-md">
                <button type="button" class="btn-remove-image absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
                    <i class="fas fa-times"></i>
                </button>
                <p class="text-xs text-green-600 mt-2">
                    <i class="fas fa-check-circle mr-1"></i>${file.name}
                </p>
            </div>
        `;
        dropzone.find('.preview-container').html(preview);

        // Evento para remover
        dropzone.find('.btn-remove-image').on('click', function (e) {
            e.stopPropagation();
            removeFile(field, dropzone);
        });
    };
    reader.readAsDataURL(file);
}

function removeFile(field, dropzone) {
    delete uploadedFiles[field];
    dropzone.find('.preview-container').html(`
        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
        <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar</p>
        <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
    `);
}

// ============================================================================
// GPS
// ============================================================================

function initializeGPS() {
    $('#btnObtenerGPS').on('click', function () {
        const btn = $(this);

        if (!navigator.geolocation) {
            Swal.fire({
                icon: 'error',
                title: 'No soportado',
                text: 'Tu navegador no soporta geolocalización',
                footer: '<small>Intenta usar Chrome, Firefox o Edge</small>'
            });
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Obteniendo ubicación...');

        // Opciones para geolocalización
        const options = {
            enableHighAccuracy: false,  // Más rápido, menos preciso
            timeout: 30000,  // 30 segundos
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            // Success callback
            function (position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                const coords = lat + ', ' + lng;

                $('#gps_coordenadas').val(coords);
                $('#coordenadasDisplay').text('Latitud: ' + lat + ', Longitud: ' + lng);
                $('#mapPreview').removeClass('hidden');

                btn.prop('disabled', false)
                    .removeClass('bg-green-600 hover:bg-green-700')
                    .addClass('bg-blue-600 hover:bg-blue-700')
                    .html('<i class="fas fa-check-circle mr-2"></i>Ubicación Obtenida');

                Swal.fire({
                    icon: 'success',
                    title: '¡Ubicación obtenida!',
                    text: coords,
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(function () {
                    btn.removeClass('bg-blue-600 hover:bg-blue-700')
                        .addClass('bg-green-600 hover:bg-green-700')
                        .html('<i class="fas fa-crosshairs mr-2"></i>Obtener Ubicación');
                }, 3000);
            },
            // Error callback
            function (error) {
                console.error('Geolocation error:', error);

                let titulo = 'Error de ubicación';
                let mensaje = '';
                let solucion = '';

                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        titulo = 'Permiso denegado';
                        mensaje = 'Has bloqueado el acceso a tu ubicación.';
                        solucion = 'Haz clic en el ícono de candado/información en la barra de direcciones y permite el acceso a la ubicación.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        titulo = 'Ubicación no disponible';
                        mensaje = 'No se pudo determinar tu ubicación.';
                        solucion = 'Verifica que tengas GPS/WiFi activo y conexión a internet.';
                        break;
                    case error.TIMEOUT:
                        titulo = 'Tiempo agotado';
                        mensaje = 'La solicitud tardó demasiado tiempo.';
                        solucion = 'Intenta nuevamente. Asegúrate de tener buena señal.';
                        break;
                    default:
                        mensaje = 'Error desconocido al obtener la ubicación.';
                        solucion = 'Intenta recargar la página o usar otro navegador.';
                }

                Swal.fire({
                    icon: 'warning',
                    title: titulo,
                    html: `<p class="mb-2">${mensaje}</p><p class="text-sm text-gray-600">${solucion}</p>`,
                    footer: '<small><strong>Nota:</strong> Este campo es opcional. Puedes guardar sin geolocalización.</small>',
                    confirmButtonText: 'Entendido'
                });

                btn.prop('disabled', false)
                    .html('<i class="fas fa-crosshairs mr-2"></i>Obtener Ubicación');
            },
            options
        );
    });
}

// ============================================================================
// VALIDACIÓN DNI
// ============================================================================

let dniCheckTimeout;

function initializeDNIValidation() {
    $('#numero_documento').on('input', function () {
        const dni = $(this).val().trim();
        const clienteId = $('#clienteId').val();

        if (dni.length < 8) {
            $('#dniError').addClass('hidden');
            $('#numero_documento').removeClass('border-red-500');
            return;
        }

        clearTimeout(dniCheckTimeout);
        dniCheckTimeout = setTimeout(function () {
            checkDniDuplicate(dni, clienteId);
        }, 500);
    });
}

function checkDniDuplicate(dni, clienteId) {
    $.get(BASE_URL + '/app/api/clientes/check_dni.php', { dni: dni, id: clienteId }, function (response) {
        if (response.exists) {
            $('#dniError').removeClass('hidden');
            $('#numero_documento').addClass('border-red-500');
        } else {
            $('#dniError').addClass('hidden');
            $('#numero_documento').removeClass('border-red-500');
        }
    }).fail(function () {
        console.error('Error al verificar DNI');
    });
}

// ============================================================================
// CARGAR DATOS
// ============================================================================

function loadClientes() {
    const search = $('#searchInput').val() || '';
    const estado = $('#filterEstado').val() || '';
    const agencia = $('#filterAgencia').val() || '';

    const url = BASE_URL + '/app/api/clientes/list.php';
    const params = { search: search, estado: estado, agencia: agencia };

    console.log('Cargando clientes...', { url, params });

    $.get(url, params, function (response) {
        console.log('Respuesta recibida:', response);
        if (response.success) {
            renderClientes(response.data.clientes);
        } else {
            console.error('Error en respuesta:', response.message);
            $('#clientesTableBody').html(`
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-red-500">
                        Error al cargar clientes: ${response.message || 'Error desconocido'}
                    </td>
                </tr>
            `);
        }
    }).fail(function (xhr, status, error) {
        console.error('Error de conexión:', { xhr, status, error });
        console.error('URL intentada:', url);
        console.error('Parámetros:', params);
        $('#clientesTableBody').html(`
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-red-500">
                    Error de conexión (${status})<br>
                    <small>Revisa la consola (F12) para más detalles</small>
                </td>
            </tr>
        `);
    });
}

function renderClientes(clientes) {
    if (!clientes || clientes.length === 0) {
        // Vista tabla (PC)
        $('#clientesTableBody').html(`
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                    <p>No se encontraron clientes</p>
                </td>
            </tr>
        `);

        // Vista cards (Móvil)
        $('#clientesCardsContainer').html(`
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
                <p class="text-gray-600">No se encontraron clientes</p>
            </div>
        `);
        return;
    }

    // Renderizar TABLA para PC
    let htmlTable = '';
    clientes.forEach(function (cliente) {
        const estadoBadge = cliente.estado === 'activo'
            ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>'
            : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>';

        const fotoHtml = cliente.foto_perfil
            ? '<img class="h-10 w-10 rounded-full object-cover" src="' + BASE_URL + '/uploads/documentos/' + cliente.foto_perfil + '" alt="' + cliente.nombre_completo + '">'
            : '<div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center"><i class="fas fa-user text-blue-600"></i></div>';

        htmlTable += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${cliente.codigo_cliente || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 flex-shrink-0">
                            ${fotoHtml}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${cliente.nombre_completo}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${cliente.numero_documento}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${cliente.telefono || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${cliente.agencia_nombre || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${estadoBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="verFicha(${cliente.id})" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Ficha">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editarCliente(${cliente.id})" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    // Renderizar CARDS para MÓVIL
    let htmlCards = '';
    clientes.forEach(function (cliente) {
        const estadoBadge = cliente.estado === 'activo'
            ? '<span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">✓ Activo</span>'
            : '<span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">✗ Inactivo</span>';

        const fotoHtml = cliente.foto_perfil
            ? '<img class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg" src="' + BASE_URL + '/uploads/documentos/' + cliente.foto_perfil + '" alt="' + cliente.nombre_completo + '">'
            : '<div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center border-4 border-white shadow-lg"><i class="fas fa-user text-white text-2xl"></i></div>';

        htmlCards += `
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-shadow">
                <!-- Header con gradiente -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 relative">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            ${fotoHtml}
                            <div class="text-white">
                                <h3 class="font-bold text-lg">${cliente.nombre_completo}</h3>
                                <p class="text-blue-100 text-sm">${cliente.codigo_cliente || 'Sin código'}</p>
                            </div>
                        </div>
                        ${estadoBadge}
                    </div>
                </div>
                
                <!-- Contenido -->
                <div class="p-4 space-y-3">
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-id-card w-6 text-blue-500"></i>
                        <span class="ml-2 text-sm"><strong>DNI:</strong> ${cliente.numero_documento}</span>
                    </div>
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-phone w-6 text-green-500"></i>
                        <span class="ml-2 text-sm"><strong>Tel:</strong> ${cliente.telefono || 'No registrado'}</span>
                    </div>
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-building w-6 text-purple-500"></i>
                        <span class="ml-2 text-sm"><strong>Agencia:</strong> ${cliente.agencia_nombre || 'Sin asignar'}</span>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-2">
                    <button onclick="verFicha(${cliente.id})" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all transform hover:scale-105 shadow-md">
                        <i class="fas fa-eye mr-2"></i>Ver Ficha
                    </button>
                    <button onclick="editarCliente(${cliente.id})" 
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition-all transform hover:scale-105 shadow-md">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </button>
                </div>
            </div>
        `;
    });

    $('#clientesTableBody').html(htmlTable);
    $('#clientesCardsContainer').html(htmlCards);
}

function loadAgencias() {
    // Cargar agencias solo para el filtro
    $.get(BASE_URL + '/app/api/agencias/list.php', function (response) {
        if (response.success) {
            let options = '<option value="">Todas las agencias</option>';

            response.data.forEach(function (agencia) {
                options += '<option value="' + agencia.id_agencia + '">' + agencia.nombre_agencia + '</option>';
            });

            $('#filterAgencia').html(options);
        }
    }).fail(function () {
        console.error('Error al cargar agencias');
    });
}

// ============================================================================
// MODAL
// ============================================================================

function openModal(clienteId) {
    isEditMode = !!clienteId;
    $('#modalTitle').text(isEditMode ? 'Editar Cliente' : 'Nuevo Cliente');

    // Resetear formulario usando jQuery
    try {
        if ($('#formCliente').length > 0) {
            $('#formCliente')[0].reset();
        }
    } catch (e) {
        console.log('No se pudo resetear el formulario:', e);
    }

    $('#clienteId').val('');
    uploadedFiles = {};

    // Resetear previews
    $('.dropzone').each(function () {
        const dropzone = $(this);
        dropzone.find('.preview-container').html(`
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar</p>
            <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
        `);
    });

    $('#mapPreview').addClass('hidden');
    $('#dniError').addClass('hidden');
    $('#numero_documento').removeClass('border-red-500');

    // Activar primera pestaña
    $('.tab-button').first().click();

    if (isEditMode) {
        loadClienteData(clienteId);
    }

    $('#modalCliente').removeClass('hidden').addClass('flex');
}

function closeModal() {
    $('#modalCliente').removeClass('flex').addClass('hidden');
}

function loadClienteData(id) {
    $.get(BASE_URL + '/app/api/clientes/get.php', { id: id }, function (response) {
        if (response.success) {
            const cliente = response.data;

            // Llenar formulario
            $('#clienteId').val(cliente.id);
            $('#nombre_completo').val(cliente.nombre_completo);
            $('#tipo_documento').val(cliente.tipo_documento);
            $('#numero_documento').val(cliente.numero_documento);
            $('#fecha_nacimiento').val(cliente.fecha_nacimiento);
            $('#genero').val(cliente.genero);
            $('#telefono').val(cliente.telefono);
            $('#email').val(cliente.email);
            $('#ocupacion').val(cliente.ocupacion);
            // $('#id_agencia').val(cliente.id_agencia); // Campo no existe en el formulario
            $('#direccion').val(cliente.direccion);
            $('#departamento').val(cliente.departamento);
            $('#municipio').val(cliente.municipio);
            $('#barrio').val(cliente.barrio);
            $('#punto_referencia').val(cliente.punto_referencia);
            $('#tipo_vivienda').val(cliente.tipo_vivienda);
            $('#gps_coordenadas').val(cliente.gps_coordenadas);

            if (cliente.gps_coordenadas) {
                $('#coordenadasDisplay').text(cliente.gps_coordenadas);
                $('#mapPreview').removeClass('hidden');
            }

            // Cargar imágenes existentes
            const imageFields = ['foto_dni_frontal', 'foto_dni_posterior', 'foto_perfil', 'foto_fachada_casa', 'foto_recibo_servicio'];

            imageFields.forEach(field => {
                if (cliente[field]) {
                    const dropzone = $(`.file-input[data-field="${field}"]`).closest('.dropzone');
                    const imageUrl = BASE_URL + '/uploads/documentos/' + cliente[field];

                    const preview = `
                        <div class="relative">
                            <img src="${imageUrl}" class="max-h-48 mx-auto rounded-lg shadow-md">
                            <button type="button" class="btn-remove-existing absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg" data-field="${field}">
                                <i class="fas fa-trash"></i>
                            </button>
                            <p class="text-xs text-green-600 mt-2">
                                <i class="fas fa-check-circle mr-1"></i>Imagen cargada
                            </p>
                        </div>
                    `;
                    dropzone.find('.preview-container').html(preview);

                    // Evento para remover imagen existente (solo visualmente para permitir nueva carga)
                    // Nota: Si el usuario guarda el formulario sin cargar una nueva, se mantendrá la anterior en DB
                    dropzone.find('.btn-remove-existing').off('click').on('click', function (e) {
                        e.stopPropagation();
                        // Resetear dropzone
                        dropzone.find('.preview-container').html(`
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">Arrastra la imagen aquí o haz clic para seleccionar</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                        `);
                    });
                }
            });
        }
    }).fail(function () {
        Swal.fire('Error', 'No se pudo cargar la información del cliente', 'error');
    });
}

// ============================================================================
// GUARDAR CLIENTE
// ============================================================================

$('#formCliente').on('submit', function (e) {
    e.preventDefault();

    // Validar campos requeridos manualmente
    // Solo validamos lo esencial para permitir guardar borradores
    const requiredFields = [
        { id: 'nombre_completo', label: 'Nombre Completo', tab: 'datos-personales' },
        { id: 'numero_documento', label: 'Número de Documento', tab: 'datos-personales' }
    ];

    for (const field of requiredFields) {
        if (!$('#' + field.id).val()) {
            Swal.fire({
                title: 'Error',
                text: `El campo ${field.label} es obligatorio`,
                icon: 'error'
            }).then(() => {
                // Cambiar a la pestaña correspondiente
                $(`.tab-button[data-tab="${field.tab.replace('tab-', '')}"]`).click();
                setTimeout(() => $('#' + field.id).focus(), 300);
            });
            return;
        }
    }

    // Validar DNI duplicado
    if ($('#dniError').is(':visible')) {
        Swal.fire('Error', 'El DNI ingresado ya está registrado', 'error');
        return;
    }

    // Validar imágenes requeridas - COMENTADO TEMPORALMENTE
    // Hasta que se agreguen las columnas a la tabla clientes
    /*
    if (!isEditMode) {
        const requiredImages = ['foto_dni_frontal', 'foto_dni_reverso', 'foto_perfil', 'foto_casa', 'foto_recibo'];
        const missingImages = requiredImages.filter(function (field) {
            return !uploadedFiles[field];
        });

        if (missingImages.length > 0) {
            Swal.fire('Error', 'Debes cargar todas las imágenes requeridas', 'error');
            // Cambiar a tab de documentación
            $('.tab-button[data-tab="documentacion"]').click();
            return;
        }
    }
    */

    // Preparar FormData
    const formData = new FormData();

    // Datos del formulario - ENVIAR TODOS LOS CAMPOS SIEMPRE
    const campos = [
        'nombre_completo', 'tipo_documento', 'numero_documento', 'fecha_nacimiento',
        'genero', 'telefono', 'email', 'ocupacion', 'direccion',
        'departamento', 'municipio', 'barrio', 'punto_referencia', 'tipo_vivienda'
    ];

    campos.forEach(function (campo) {
        const valor = $('#' + campo).val();
        // Enviar SIEMPRE, incluso si está vacío
        formData.append(campo, valor || '');
    });

    // Agencia - SIEMPRE usar la del usuario logueado
    formData.append('id_agencia', USER_AGENCIA_ID);

    // GPS coordinates - send always
    const gpsValue = $('#gps_coordenadas').val();
    formData.append('gps_coordenadas', gpsValue || '');

    // Agregar ID si es edición
    if (isEditMode) {
        formData.append('id', $('#clienteId').val());
    }

    // Agregar archivos
    Object.keys(uploadedFiles).forEach(function (field) {
        formData.append(field, uploadedFiles[field]);
    });

    // Enviar
    const url = isEditMode
        ? BASE_URL + '/app/api/clientes/update.php'
        : BASE_URL + '/app/api/clientes/create_with_files.php';

    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: isEditMode ? 'Cliente actualizado correctamente' : 'Cliente registrado correctamente',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Ver Ficha',
                    cancelButtonText: 'Cerrar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.location.href = BASE_URL + '/public/admin/ficha_cliente.php?id=' + response.data.id;
                    } else {
                        closeModal();
                        loadClientes();
                    }
                });
            } else {
                Swal.fire('Error', response.message || 'Error al guardar cliente', 'error');
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            Swal.fire('Error', msg, 'error');
        }
    });
});

// ============================================================================
// ACCIONES
// ============================================================================

function verFicha(id) {
    window.location.href = BASE_URL + '/public/admin/ficha_cliente.php?id=' + id;
}

function editarCliente(id) {
    openModal(id);
}

function eliminarCliente(id) {
    Swal.fire({
        title: '¿Eliminar cliente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/app/api/clientes/delete.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', 'Cliente eliminado correctamente', 'success');
                        loadClientes();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            });
        }
    });
}

// ============================================================================
// FILTROS Y EVENT HANDLERS
// ============================================================================

function initializeEventHandlers() {
    // Botón nuevo cliente - ya manejado por el script inline
    // pero agregamos soporte para el archivo externo también
    $('#btnNuevoCliente').off('click').on('click', function () {
        openModal();
    });

    $('#btnCerrarModal, #btnCancelar').on('click', function () {
        closeModal();
    });

    $('#searchInput').on('input', debounce(loadClientes, 500));
    $('#filterEstado, #filterAgencia').on('change', loadClientes);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function () {
            clearTimeout(timeout);
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
