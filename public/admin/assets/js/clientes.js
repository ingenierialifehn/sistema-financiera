/**
 * GestiÃ³n de Clientes - JavaScript
 * Incluye: Drag & Drop, ValidaciÃ³n DNI, GPS, Tabs
 */

let uploadedFiles = {};
let isEditMode = false;

// Esperar a que el DOM y jQuery estÃ©n listos
document.addEventListener('DOMContentLoaded', function () {
    console.log('Clientes.js cargado');

    // Verificar que jQuery estÃ© disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no estÃ¡ cargado');
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
            // Solo abrir el selector si NO se hizo clic en el botÃ³n de eliminar
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
        Swal.fire('Error', 'Solo se permiten imÃ¡genes', 'error');
        return;
    }

    // Validar tamaÃ±o (5MB)
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

    const labels = {
        'foto_dni_frontal': 'DNI Frontal',
        'foto_dni_posterior': 'DNI Reverso',
        'foto_perfil': 'Foto de Perfil',
        'foto_fachada_casa': 'Foto de Casa',
        'foto_recibo_servicio': 'Foto Recibo'
    };
    const labelText = labels[field] || 'Foto';

    dropzone.find('.preview-container').html(`
        <div class="hidden lg:block">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-600">Arrastra la imagen aquÃ­ o haz clic para seleccionar</p>
        </div>
        <div class="lg:hidden flex flex-col items-center justify-center py-2">
            <div class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                <i class="fas fa-camera text-3xl text-blue-600"></i>
            </div>
            <p class="text-blue-700 font-bold text-lg">Tomar ${labelText}</p>
            <p class="text-sm text-gray-500">Toca para abrir cÃ¡mara</p>
        </div>
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
                text: 'Tu navegador no soporta geolocalizaciÃ³n',
                footer: '<small>Intenta usar Chrome, Firefox o Edge</small>'
            });
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Obteniendo ubicaciÃ³n...');

        // Opciones para geolocalizaciÃ³n
        const options = {
            enableHighAccuracy: false,  // MÃ¡s rÃ¡pido, menos preciso
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
                    .html('<i class="fas fa-check-circle mr-2"></i>UbicaciÃ³n Obtenida');

                Swal.fire({
                    icon: 'success',
                    title: 'Â¡UbicaciÃ³n obtenida!',
                    text: coords,
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(function () {
                    btn.removeClass('bg-blue-600 hover:bg-blue-700')
                        .addClass('bg-green-600 hover:bg-green-700')
                        .html('<i class="fas fa-crosshairs mr-2"></i>Obtener UbicaciÃ³n');
                }, 3000);
            },
            // Error callback
            function (error) {
                console.error('Geolocation error:', error);

                let titulo = 'Error de ubicaciÃ³n';
                let mensaje = '';
                let solucion = '';

                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        titulo = 'Permiso denegado';
                        mensaje = 'Has bloqueado el acceso a tu ubicaciÃ³n.';
                        solucion = 'Haz clic en el Ã­cono de candado/informaciÃ³n en la barra de direcciones y permite el acceso a la ubicaciÃ³n.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        titulo = 'UbicaciÃ³n no disponible';
                        mensaje = 'No se pudo determinar tu ubicaciÃ³n.';
                        solucion = 'Verifica que tengas GPS/WiFi activo y conexiÃ³n a internet.';
                        break;
                    case error.TIMEOUT:
                        titulo = 'Tiempo agotado';
                        mensaje = 'La solicitud tardÃ³ demasiado tiempo.';
                        solucion = 'Intenta nuevamente. AsegÃºrate de tener buena seÃ±al.';
                        break;
                    default:
                        mensaje = 'Error desconocido al obtener la ubicaciÃ³n.';
                        solucion = 'Intenta recargar la pÃ¡gina o usar otro navegador.';
                }

                Swal.fire({
                    icon: 'warning',
                    title: titulo,
                    html: `<p class="mb-2">${mensaje}</p><p class="text-sm text-gray-600">${solucion}</p>`,
                    footer: '<small><strong>Nota:</strong> Este campo es opcional. Puedes guardar sin geolocalizaciÃ³n.</small>',
                    confirmButtonText: 'Entendido'
                });

                btn.prop('disabled', false)
                    .html('<i class="fas fa-crosshairs mr-2"></i>Obtener UbicaciÃ³n');
            },
            options
        );
    });
}

// ============================================================================
// VALIDACIÃ“N DNI
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
        console.error('Error de conexiÃ³n:', { xhr, status, error });
        console.error('URL intentada:', url);
        console.error('ParÃ¡metros:', params);
        $('#clientesTableBody').html(`
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-red-500">
                    Error de conexiÃ³n (${status})<br>
                    <small>Revisa la consola (F12) para mÃ¡s detalles</small>
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

        // Vista cards (MÃ³vil)
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
                    ${hasMissingData(cliente) ? '<span class="text-yellow-500 mr-2" title="Datos faltantes"><i class="fas fa-exclamation-triangle"></i></span>' : ''}
                    <button onclick="verFicha(${cliente.id})" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Ficha">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${PERMISSIONS.edit ? `
                    <button onclick="editarCliente(${cliente.id})" class="text-indigo-600 hover:text-indigo-900" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    // Renderizar CARDS para MÃ“VIL
    let htmlCards = '';
    clientes.forEach(function (cliente) {
        const estadoBadge = cliente.estado === 'activo'
            ? '<span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800">âœ“ Activo</span>'
            : '<span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">âœ— Inactivo</span>';

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
                                <p class="text-blue-100 text-sm">${cliente.codigo_cliente || 'Sin cÃ³digo'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                             ${hasMissingData(cliente) ? '<i class="fas fa-exclamation-triangle text-yellow-300 text-lg animate-pulse" title="Datos faltantes"></i>' : ''}
                             ${estadoBadge}
                        </div>
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
                    ${PERMISSIONS.edit ? `
                    <button onclick="editarCliente(${cliente.id})" 
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition-all transform hover:scale-105 shadow-md">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
    });

    $('#clientesTableBody').html(htmlTable);
    $('#clientesCardsContainer').html(htmlCards);
}

function hasMissingData(cliente) {
    // Campos que si faltan general alerta (excluyendo email y ubicación)
    // Ubicación asumimos: direccion, departamento, municipio, barrio, punto_referencia, gps_coordenadas
    // Mandatory fields are enforced on save, so we check "optional" fields that are not location/email.
    // Candidates: genero, tipo_vivienda.
    const fieldsToCheck = ['genero', 'tipo_vivienda'];

    for (const field of fieldsToCheck) {
        if (!cliente[field] || cliente[field] === '') {
            return true;
        }
    }
    return false;
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
        const field = dropzone.find('.file-input').data('field');
        const labels = {
            'foto_dni_frontal': 'DNI Frontal',
            'foto_dni_posterior': 'DNI Reverso',
            'foto_perfil': 'Foto de Perfil',
            'foto_fachada_casa': 'Foto de Casa',
            'foto_recibo_servicio': 'Foto Recibo'
        };
        const labelText = labels[field] || 'Foto';

        dropzone.find('.preview-container').html(`
            <div class="hidden lg:block">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">Arrastra la imagen aquÃ­ o haz clic para seleccionar</p>
            </div>
            <div class="lg:hidden flex flex-col items-center justify-center py-2">
                <div class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                    <i class="fas fa-camera text-3xl text-blue-600"></i>
                </div>
                <p class="text-blue-700 font-bold text-lg">Tomar ${labelText}</p>
                <p class="text-sm text-gray-500">Toca para abrir cÃ¡mara</p>
            </div>
            <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
        `);
    });

    $('#mapPreview').addClass('hidden');
    $('#dniError').addClass('hidden');
    $('#numero_documento').removeClass('border-red-500');

    // Activar primera pestaÃ±a
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

            // Cargar imÃ¡genes existentes
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
                    // Nota: Si el usuario guarda el formulario sin cargar una nueva, se mantendrÃ¡ la anterior en DB
                    dropzone.find('.btn-remove-existing').off('click').on('click', function (e) {
                        e.stopPropagation();
                        // Resetear dropzone
                        const labels = {
                            'foto_dni_frontal': 'DNI Frontal',
                            'foto_dni_posterior': 'DNI Reverso',
                            'foto_perfil': 'Foto de Perfil',
                            'foto_fachada_casa': 'Foto de Casa',
                            'foto_recibo_servicio': 'Foto Recibo'
                        };
                        const labelText = labels[field] || 'Foto';

                        dropzone.find('.preview-container').html(`
                            <div class="hidden lg:block">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-600">Arrastra la imagen aquÃ­ o haz clic para seleccionar</p>
                            </div>
                            <div class="lg:hidden flex flex-col items-center justify-center py-2">
                                <div class="bg-blue-100 rounded-full p-4 mb-2 shadow-sm ring-4 ring-blue-50">
                                    <i class="fas fa-camera text-3xl text-blue-600"></i>
                                </div>
                                <p class="text-blue-700 font-bold text-lg">Tomar ${labelText}</p>
                                <p class="text-sm text-gray-500">Toca para abrir cÃ¡mara</p>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG (Max. 5MB)</p>
                        `);
                    });
                }
            });
        }
    }).fail(function () {
        Swal.fire('Error', 'No se pudo cargar la informaciÃ³n del cliente', 'error');
    });
}

// ============================================================================
// GUARDAR CLIENTE
// ============================================================================

$('#formCliente').on('submit', function (e) {
    e.preventDefault();

    // Validar campos requeridos
    const requiredFields = [
        { id: 'nombre_completo', label: 'Nombre Completo', tab: 'datos-personales' },
        { id: 'numero_documento', label: 'Número de Documento', tab: 'datos-personales' },
        { id: 'fecha_nacimiento', label: 'Fecha de Nacimiento', tab: 'datos-personales' },
        { id: 'telefono', label: 'Teléfono', tab: 'datos-personales' },
        { id: 'ocupacion', label: 'Ocupación', tab: 'datos-personales' }
    ];

    for (const field of requiredFields) {
        if (!$('#' + field.id).val()) {
            Swal.fire({
                title: 'Campo Requerido',
                text: `El campo ${field.label} es obligatorio`,
                icon: 'warning'
            }).then(() => {
                $(`.tab-button[data-tab="${field.tab.replace('tab-', '')}"]`).click();
                setTimeout(() => $('#' + field.id).focus(), 300);
            });
            return;
        }
    }

    // Validar Edad (18 - 69 años)
    const fechaNac = new Date($('#fecha_nacimiento').val());
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const m = hoy.getMonth() - fechaNac.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }

    if (edad < 18 || edad >= 70) {
        Swal.fire({
            title: 'Edad Inválida',
            text: `El cliente tiene ${edad} años. Solo se permiten clientes entre 18 y 69 años.`,
            icon: 'error'
        });
        return;
    }

    // Validar DNI duplicado
    if ($('#dniError').is(':visible')) {
        Swal.fire('Error', 'El DNI ingresado ya está registrado', 'error');
        return;
    }

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
        title: 'Â¿Eliminar cliente?',
        text: 'Esta acciÃ³n no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'SÃ­, eliminar',
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
                    Swal.fire('Error', 'Error de conexiÃ³n', 'error');
                }
            });
        }
    });
}

// ============================================================================
// FILTROS Y EVENT HANDLERS
// ============================================================================

function initializeEventHandlers() {
    // BotÃ³n nuevo cliente - ya manejado por el script inline
    // pero agregamos soporte para el archivo externo tambiÃ©n
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

// ========================================================================================================================
// LOGICA DE FILTROS MOVILES
// ========================================================================================================================

function openMobileFilters() {
    // Sincronizar valores actuales del escritorio al mÃ³vil
    $('#searchInputMobile').val($('#searchInput').val());

    const currentState = $('#filterEstado').val();
    setMobileFilterState(currentState || 'all');

    // Sincronizar agencia
    const currentAgencia = $('#filterAgencia').val();
    if (currentAgencia) {
        $('#filterAgenciaMobile').val(currentAgencia);
    } else if (typeof USER_AGENCIA_ID !== 'undefined' && USER_AGENCIA_ID !== null) {
        // Pre-seleccionar agencia del usuario si no hay filtro activo
        $('#filterAgenciaMobile').val(USER_AGENCIA_ID);
    }

    $('#modalMobileFilters').removeClass('hidden').addClass('flex');
    $('body').addClass('overflow-hidden'); // Prevenir scroll
}

function closeMobileFilters() {
    $('#modalMobileFilters').removeClass('flex').addClass('hidden');
    $('body').removeClass('overflow-hidden');
}

function setMobileFilterState(state) {
    // Reset buttons
    $('.mobile-filter-btn').removeClass('active border-blue-600 bg-blue-50 text-blue-700').addClass('border-gray-200 text-gray-600');

    // Set active button
    let btnId;
    if (state === 'activo') btnId = '#btnStateActivo';
    else if (state === 'inactivo') btnId = '#btnStateInactivo';
    else btnId = '#btnStateAll'; // default all

    $(btnId).removeClass('border-gray-200 text-gray-600 hover:bg-gray-50').addClass('active border-blue-600 bg-blue-50 text-blue-700');

    // Set hidden input value
    let val = state;
    if (state === 'all') val = '';
    $('#filterEstadoMobile').val(val);
}

function resetMobileFilters() {
    $('#searchInputMobile').val('');
    setMobileFilterState('all');

    // Resetear agencia a la del usuario si existe, sino a todas
    if (typeof USER_AGENCIA_ID !== 'undefined' && USER_AGENCIA_ID !== null) {
        $('#filterAgenciaMobile').val(USER_AGENCIA_ID);
    } else {
        $('#filterAgenciaMobile').val('');
    }
}

function applyMobileFilters() {
    // Transferir valores del mÃ³vil al escritorio (que es lo que usa loadClientes)
    $('#searchInput').val($('#searchInputMobile').val());
    $('#filterEstado').val($('#filterEstadoMobile').val());
    $('#filterAgencia').val($('#filterAgenciaMobile').val());

    // Actualizar input de visualizaciÃ³n
    const term = $('#searchInputMobile').val();
    if (term) {
        $('#searchInputMobileDisplay').val(term);
    } else {
        $('#searchInputMobileDisplay').val('');
    }

    // Actualizar badges
    updateFilterBadges();

    // Recargar clientes
    loadClientes();
    closeMobileFilters();
}

function updateFilterBadges() {
    const badgesContainer = $('#activeFiltersBadges');
    badgesContainer.html('').addClass('hidden');

    const agencias = $('#filterAgenciaMobile option:selected').text();
    const estado = $('#filterEstadoMobile').val();

    let hasFilters = false;

    // Badge Agencia
    if ($('#filterAgenciaMobile').val()) {
        badgesContainer.append(`
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                ${agencias}
            </span>
        `);
        hasFilters = true;
    }

    // Badge Estado
    if (estado) {
        badgesContainer.append(`
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 uppercase">
                ${estado}
            </span>
        `);
        hasFilters = true;
    }

    if (hasFilters) {
        badgesContainer.removeClass('hidden');
    }
}

// Modificar loadAgencias para llenar tambiÃ©n el select mÃ³vil
const originalLoadAgencias = window.loadAgencias || loadAgencias;
window.loadAgencias = function () {
    // Llamar a la lÃ³gica original para llenar el select de escritorio (que ya reescribirÃ­amos si supieramos su cÃ³digo exacto, 
    // pero mejor hacemos nuestra propia llamada AJAX para asegurarnos que llene AMBOS)

    $.get(BASE_URL + '/app/api/agencias/list.php', function (response) {
        if (response.success) {
            let options = '<option value="">Todas las agencias</option>';

            response.data.forEach(function (agencia) {
                options += '<option value="' + agencia.id_agencia + '">' + agencia.nombre_agencia + '</option>';
            });

            // Llenar ambos selects
            $('#filterAgencia').html(options);
            $('#filterAgenciaMobile').html(options);

            // Pre-seleccionar si el usuario tiene agencia
            if (typeof USER_AGENCIA_ID !== 'undefined' && USER_AGENCIA_ID !== null) {
                $('#filterAgencia').val(USER_AGENCIA_ID);
                $('#filterAgenciaMobile').val(USER_AGENCIA_ID);

                // Deshabilitar cambio si se desea forzar (opcional, el usuario pidiÃ³ "predeterminado", no "bloqueado", pero a veces es mejor bloquear)
                // Usaremos predeterminado por ahora.
            }
        }
    }).fail(function () {
        console.error('Error al cargar agencias');
    });
};

// Sobreescribir loadClientes para asegurar que se ejecute correctamente al inicio
// (Aunque loadClientes usa los inputs de escritorio, que nosotros actualizamos, asÃ­ que deberÃ­a funcionar bien)

$(document).ready(function () {
    // Inicializar badges si hay filtros por defecto
    setTimeout(updateFilterBadges, 1000); // Esperar a que carguen agencias
});
