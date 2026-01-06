/**
 * Colaboradores JavaScript - Gestión de colaboradores (Gestión de Personal)
 */

let currentPage = 1;
let currentSearch = '';
let currentAgencia = '';
let currentPuesto = '';

// Inicializar
$(document).ready(function () {
    loadColaboradores();

    // Eventos
    $('#btnNuevoColaborador').on('click', function () {
        openModal();
    });

    $('#btnBuscar').on('click', function () {
        currentPage = 1;
        currentSearch = $('#searchInput').val();
        currentAgencia = $('#filterAgencia').val();
        currentPuesto = $('#filterPuesto').val();
        loadColaboradores();
    });

    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) $('#btnBuscar').click();
    });

    $('#colaboradorForm').on('submit', function (e) {
        e.preventDefault();
        saveColaborador();
    });

    $('#btnCerrarModal, #btnCancelar').on('click', function () {
        closeModal();
    });

    // Toggle Section B (Usuario)
    $('#tieneAcceso').on('change', function () {
        toggleUsuarioSection($(this).is(':checked'));
    });
});

// Cargar colaboradores
function loadColaboradores(page = 1) {
    currentPage = page;
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');

    let url = `${BASE_URL}/app/api/colaboradores/list.php?page=${page}&limit=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentAgencia) url += `&agencia=${encodeURIComponent(currentAgencia)}`;
    if (currentPuesto) url += `&puesto=${encodeURIComponent(currentPuesto)}`;

    $.ajax({
        url: url,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function (response) {
            if (response.success) {
                renderColaboradores(response.data.colaboradores);
                renderPagination(response.data.pagination);
            }
        },
        error: handleAjaxError
    });
}

// Renderizar tabla
function renderColaboradores(colaboradores) {
    const tbody = $('#colaboradoresTableBody');

    if (!colaboradores || colaboradores.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay colaboradores registrados</td></tr>');
        return;
    }

    let html = '';
    colaboradores.forEach(function (c) {
        const hasUser = c.usuario_id != null;
        const userIcon = hasUser ? `<i class="fas fa-key text-yellow-500 ml-2" title="Usuario Vinculado: ${c.usuario_nombre}"></i>` : '';
        const estadoLabel = c.estado_laboral || 'activo';

        html += `
            <tr class="hover:bg-indigo-50/30 transition border-b border-gray-100 last:border-0">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm mr-3">
                             ${c.nombre_completo.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 flex items-center">
                                ${escapeHtml(c.nombre_completo)}
                                ${userIcon}
                            </div>
                            ${hasUser ? `<div class="text-xs text-gray-500 font-mono mt-0.5">@${escapeHtml(c.usuario_nombre)}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="font-medium">${escapeHtml(c.dni)}</div>
                    <div class="text-xs text-gray-400">${escapeHtml(c.email)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div class="font-medium">${escapeHtml(c.puesto)}</div>
                    <div class="text-xs text-gray-400">${escapeHtml(c.agencia)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${getEstadoLaboralClass(estadoLabel)}">
                        ${stateToLabel(estadoLabel)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="editColaborador(${c.id})" class="text-indigo-600 hover:text-indigo-900 mx-2 p-1 hover:bg-indigo-50 rounded transition" title="Editar">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <!-- Mostrar botón de Traspaso si aplica (ejemplo: si estado es 'despido' o similar) -->
                    <!-- Se implementará lógica específica en layout o aquí -->
                </td>
            </tr>
        `;
    });

    tbody.html(html);
}

// Abrir modal (Crear/Editar)
function openModal(colaborador = null) {
    $('#colaboradorForm')[0].reset();
    $('#colaboradorId').val('');
    $('#usuarioId').val('');
    $('#seccionUsuario').addClass('hidden');
    $('#tieneAcceso').prop('checked', false);

    $('#modalTitle').text(colaborador ? 'Editar Colaborador' : 'Nuevo Colaborador');

    if (colaborador) {
        populateModal(colaborador);
    } else {
        // Nuevo
        $('#estadoLaboral').val('activo');
        toggleUsuarioSection(false);
    }

    $('#colaboradorModal').removeClass('hidden').addClass('flex');
}

function populateModal(c) {
    $('#colaboradorId').val(c.id);
    $('#dni').val(c.dni);
    $('#nombreCompleto').val(c.nombre_completo);
    $('#email').val(c.email);
    $('#sueldoBase').val(c.sueldo_base);
    $('#agencia').val(c.agencia);
    $('#puesto').val(c.puesto);
    $('#estadoLaboral').val(c.estado_laboral || 'activo');

    // Logs de auditoría
    $('#auditLog').removeClass('hidden');
    // Esto es simulado, idealmente el backend retorna created_by
    $('#logCreadoPor').text('Sistema');
    $('#logModificado').text(c.updated_at || c.created_at);

    if (c.usuario_vinculado) {
        $('#tieneAcceso').prop('checked', true);
        toggleUsuarioSection(true);

        const u = c.usuario_vinculado;
        $('#usuarioId').val(u.id);
        $('#usuario').val(u.usuario).prop('disabled', true); // User cannot change username simply
        $('#idRol').val(u.id_rol);
        $('#idJefeDirecto').val(u.id_jefe_directo); // Assumed loaded

        // Password logic updates
        $('#password').removeAttr('required').attr('placeholder', 'Solo llenar para cambiar');
        $('#confirmPassword').removeAttr('required').attr('placeholder', 'Solo llenar para cambiar');
    } else {
        toggleUsuarioSection(false);
        $('#auditLog').addClass('hidden');
    }
}

function toggleUsuarioSection(show) {
    const section = $('#seccionUsuario');
    if (show) {
        section.removeClass('hidden');
        // Add required
        if (!$('#usuarioId').val()) { // Only if new user
            $('#usuario').attr('required', 'required');
            $('#password').attr('required', 'required');
            $('#idRol').attr('required', 'required');
        }
    } else {
        section.addClass('hidden');
        $('#usuario').removeAttr('required');
        $('#password').removeAttr('required');
        $('#idRol').removeAttr('required');
    }
}

function saveColaborador() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const id = $('#colaboradorId').val();
    const isEdit = id !== '';

    const data = {
        dni: $('#dni').val(),
        nombre_completo: $('#nombreCompleto').val(),
        email: $('#email').val(),
        sueldo_base: parseFloat($('#sueldoBase').val()),
        agencia: $('#agencia').val(),
        puesto: $('#puesto').val(),
        estado_laboral: $('#estadoLaboral').val(),

        // Usuario Logic
        crear_usuario: $('#tieneAcceso').is(':checked'),
        usuario: $('#usuario').val(),
        id_rol: $('#idRol').val(),
        id_jefe_directo: $('#idJefeDirecto').val()
    };

    // Password
    const pass = $('#password').val();
    const confirm = $('#confirmPassword').val();

    if (data.crear_usuario) {
        if (!isEdit && !pass) {
            showAlert('error', 'La contraseña es obligatoria para nuevos usuarios');
            return;
        }
        if (pass && pass !== confirm) {
            showAlert('error', 'Las contraseñas no coinciden');
            return;
        }
        if (pass) data.password = pass;
    }

    // Validar Saldo vs Estado Laboral (Basic Frontend Check)
    // Nota: El backend tiene la validación final.
    if ((data.estado_laboral === 'despido' || data.estado_laboral === 'renuncia') && data.crear_usuario) {
        // Warning about box closing is handled by Backend error usually, 
        // but creating a visual warning here is good practice if we had the balance.
    }

    if (isEdit) {
        data.id = id;
        data.usuario_id = $('#usuarioId').val(); // If linking/unlinking
    }

    const url = isEdit ? `${BASE_URL}/app/api/colaboradores/update.php` : `${BASE_URL}/app/api/colaboradores/create.php`;
    // Note: update.php needs to be implemented/compatible

    $.ajax({
        url: url,
        method: isEdit ? 'PUT' : 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                showAlert('success', 'Registro guardado exitosamente');
                closeModal();
                loadColaboradores(currentPage);
            }
        },
        error: handleAjaxError
    });
}

function editColaborador(id) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    $.ajax({
        url: `${BASE_URL}/app/api/colaboradores/get.php?id=${id}`,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function (response) {
            if (response.success) {
                openModal(response.data);
            }
        },
        error: handleAjaxError
    });
}

function closeModal() {
    $('#colaboradorModal').removeClass('flex').addClass('hidden');
}

// Helpers
function getEstadoLaboralClass(estado) {
    switch (estado) {
        case 'activo': return 'bg-green-100 text-green-800';
        case 'vacaciones': return 'bg-blue-100 text-blue-800';
        case 'suspendido': return 'bg-orange-100 text-orange-800';
        case 'despido': return 'bg-red-100 text-red-800';
        case 'renuncia': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function stateToLabel(txt) {
    return txt.charAt(0).toUpperCase() + txt.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>"']/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function handleAjaxError(xhr) {
    if (xhr.status === 401 || xhr.status === 403) {
        window.location.href = BASE_URL + '/public/login.php';
        return;
    }
    const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Ocurrió un error inesperado';
    showAlert('error', msg);
    if (xhr.responseJSON && xhr.responseJSON.errors) {
        console.error(xhr.responseJSON.errors);
    }
}

function showAlert(type, message) {
    Swal.fire({
        icon: type,
        title: type === 'success' ? 'Éxito' : 'Error',
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}
