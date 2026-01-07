/**
 * Roles y Permisos Detallados - JavaScript
 * Maneja permisos granulares a nivel de botón/acción
 */

let editingRoleId = null;

$(document).ready(function () {
    loadRoles();
});

function loadRoles() {
    $.get(BASE_URL + '/app/api/roles/index.php', function (response) {
        if (response.success) {
            renderRolesTable(response.data);
        } else {
            $('#rolesTableBody').html('<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error al cargar roles</td></tr>');
        }
    }).fail(function () {
        $('#rolesTableBody').html('<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error de conexión</td></tr>');
    });
}

function renderRolesTable(roles) {
    let html = '';
    roles.forEach(role => {
        let permisosHtml = '';

        // Check for legacy/admin full access
        if (role.permisos.todos) {
            permisosHtml = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Acceso Total</span>';
        } else {
            // Count total permissions
            let totalPermissions = 0;
            let modulesWithPermissions = 0;
            let isReadOnly = role.permisos.readonly === true;

            for (let module in role.permisos) {
                if (module === 'readonly') continue;

                if (typeof role.permisos[module] === 'object') {
                    const permCount = Object.keys(role.permisos[module]).length;
                    if (permCount > 0) {
                        modulesWithPermissions++;
                        totalPermissions += permCount;
                    }
                } else if (role.permisos[module] === true) {
                    modulesWithPermissions++;
                    totalPermissions++;
                }
            }

            if (isReadOnly) {
                permisosHtml += '<span class="px-2 mr-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-lock mr-1"></i>Solo Lectura</span>';
            }

            permisosHtml += `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800"><i class="fas fa-cube mr-1"></i>${modulesWithPermissions} Módulos</span>`;
            permisosHtml += `<span class="px-2 ml-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-key mr-1"></i>${totalPermissions} Permisos</span>`;
        }

        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <i class="fas fa-user-tag text-indigo-600 mr-2"></i>
                        <div class="text-sm font-medium text-gray-900">${role.nombre_rol}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-500">${role.descripcion || '-'}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">${permisosHtml}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${role.estado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${role.estado}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="editRole(${role.id_rol})" class="text-indigo-600 hover:text-indigo-900 mr-3 btn-edit">
                        <i class="fas fa-edit mr-1"></i>Editar
                    </button>
                </td>
            </tr>
        `;
    });
    $('#rolesTableBody').html(html);
}

function openModal(isEdit = false) {
    if (!isEdit) {
        editingRoleId = null;
    }
    $('#modal-title').text(isEdit ? 'Editar Rol' : 'Nuevo Rol');
    $('#roleModal').removeClass('hidden');
}

function closeModal() {
    $('#roleModal').addClass('hidden');
    $('#roleForm')[0].reset();
    $('.permiso-check').prop('checked', false);
    $('#readonly_toggle').prop('checked', false);

    // Colapsar todos los módulos
    $('[id^="perms-"]').addClass('hidden');
    $('[id^="icon-"]').removeClass('rotate-90');

    editingRoleId = null;
}

function editRole(idRol) {
    $.get(`${BASE_URL}/app/api/roles/get.php?id=${idRol}`, function (response) {
        if (response.success) {
            const role = response.data;
            editingRoleId = role.id_rol;

            $('#id_rol').val(role.id_rol);
            $('#nombre_rol').val(role.nombre_rol);
            $('#descripcion').val(role.descripcion || '');

            // Reset inputs
            $('.permiso-check').prop('checked', false);
            $('#readonly_toggle').prop('checked', false);

            // Populate permissions
            if (role.permisos) {
                if (role.permisos.readonly) {
                    $('#readonly_toggle').prop('checked', true);
                }

                for (let module in role.permisos) {
                    if (module === 'readonly' || module === 'todos') continue;

                    const val = role.permisos[module];

                    if (val === true) {
                        // Legacy: true means all permissions for this module
                        $(`input[data-module="${module}"]`).prop('checked', true);
                    } else if (typeof val === 'object') {
                        // Detailed permissions: check specific permissions
                        for (let permission in val) {
                            if (val[permission] === true) {
                                $(`input[data-module="${module}"][data-permission="${permission}"]`).prop('checked', true);
                            }
                        }
                    }
                }
            }

            openModal(true);
        } else {
            Swal.fire('Error', 'No se pudo cargar el rol', 'error');
        }
    });
}

function saveRole() {
    const nombre = $('#nombre_rol').val().trim();
    const descripcion = $('#descripcion').val().trim();

    if (!nombre) {
        Swal.fire('Error', 'El nombre del rol es requerido', 'error');
        return;
    }

    // Build Permissions Object with detailed structure
    const permissions = {};

    if ($('#readonly_toggle').is(':checked')) {
        permissions.readonly = true;
    }

    $('.permiso-check:checked').each(function () {
        const module = $(this).data('module');
        const permission = $(this).data('permission');

        if (!permissions[module]) {
            permissions[module] = {};
        }
        permissions[module][permission] = true;
    });

    if (Object.keys(permissions).length === 0 || (Object.keys(permissions).length === 1 && permissions.readonly)) {
        Swal.fire('Error', 'Debe seleccionar al menos un permiso', 'error');
        return;
    }

    const data = {
        nombre_rol: nombre,
        descripcion: descripcion,
        permisos: permissions
    };

    let url = BASE_URL + '/app/api/roles/create.php';
    let method = 'POST';

    if (editingRoleId) {
        url = BASE_URL + '/app/api/roles/update.php';
        data.id_rol = editingRoleId;
    }

    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', response.message || 'Rol guardado exitosamente', 'success');
                closeModal();
                loadRoles();
            } else {
                Swal.fire('Error', response.message || 'Error al guardar', 'error');
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message || 'Error al guardar el rol';
            Swal.fire('Error', msg, 'error');
        }
    });
}

// --- Lógica de Gestión de Puestos ---

function openPuestosModal() {
    $('#puestosModal').removeClass('hidden');
    loadPuestos();
}

function closePuestosModal() {
    $('#puestosModal').addClass('hidden');
    resetPuestoForm();
}

function resetPuestoForm() {
    $('#puestoForm')[0].reset();
    $('#id_puesto').val('');
    $('#puestoFormTitle').text('Nuevo Puesto');
}

function loadPuestos() {
    $.get(BASE_URL + '/app/api/puestos/list.php', function (response) {
        if (response.success) {
            renderPuestosTable(response.data);
        } else {
            Swal.fire('Error', 'No se pudieron cargar los puestos', 'error');
        }
    });
}

function renderPuestosTable(puestos) {
    let html = '';
    if (puestos.length === 0) {
        html = '<tr><td colspan="3" class="px-4 py-2 text-center text-gray-500">No hay puestos registrados</td></tr>';
    } else {
        puestos.forEach(p => {
            html += `
                <tr>
                    <td class="px-4 py-2 border-b text-sm text-gray-700">${p.nombre_puesto}</td>
                    <td class="px-4 py-2 border-b text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${p.estado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${p.estado}
                        </span>
                    </td>
                    <td class="px-4 py-2 border-b text-sm text-right space-x-2">
                        <button onclick='editPuesto(${JSON.stringify(p)})' class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    $('#puestosTableBody').html(html);
}

function editPuesto(puesto) {
    $('#id_puesto').val(puesto.id_puesto);
    $('#nombre_puesto').val(puesto.nombre_puesto);
    $('#estado_puesto').val(puesto.estado);
    $('#puestoFormTitle').text('Editar Puesto');
}

// Handle Form Submit
$('#puestoForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#id_puesto').val();
    const data = {
        nombre_puesto: $('#nombre_puesto').val().trim(),
        estado: $('#estado_puesto').val()
    };

    if (id) data.id_puesto = id;

    const url = id
        ? BASE_URL + '/app/api/puestos/update.php'
        : BASE_URL + '/app/api/puestos/create.php';

    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                Swal.fire('Éxito', 'Operación realizada correctamente', 'success');
                resetPuestoForm();
                loadPuestos();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Error al guardar', 'error');
        }
    });
});
