<?php
/**
 * Página: Gestión de Roles
 */
$pageTitle = 'Roles y Permisos';
require_once __DIR__ . '/../includes/layout.php';

// Redirigir si no tiene permiso
if (!Auth::hasPermission('seguridad')) {
    header('Location: ' . base_url('public/admin/dashboard.php'));
    exit;
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-shield-alt mr-2"></i>Roles y Permisos
        </h1>
        <div class="space-x-2">
            <button onclick="openPuestosModal()"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded shadow transition">
                <i class="fas fa-briefcase mr-2"></i>Gestión de Puestos
            </button>
            <button onclick="openModal()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition">
                <i class="fas fa-plus mr-2"></i>Nuevo Rol
            </button>
        </div>
    </div>

    <!-- Lista de Roles -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="rolesTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Cargando roles...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar Rol -->
<div id="roleModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Nuevo Rol
                        </h3>
                        <div class="mt-4">
                            <form id="roleForm">
                                <input type="hidden" id="id_rol" name="id_rol">

                                <div class="grid grid-cols-1 gap-4 mb-4">
                                    <div>
                                        <label for="nombre_rol" class="block text-sm font-medium text-gray-700">Nombre
                                            del Rol *</label>
                                        <input type="text" name="nombre_rol" id="nombre_rol" required
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label for="descripcion"
                                            class="block text-sm font-medium text-gray-700">Descripción</label>
                                        <textarea name="descripcion" id="descripcion" rows="2"
                                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                </div>

                                <div class="mb-4 bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-800">Modo Solo Lectura</h4>
                                            <p class="text-xs text-gray-600">Si está activo, se ocultarán los botones de
                                                guardar, editar y eliminar en todo el sistema.</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="readonly_toggle" class="sr-only peer">
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500">
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-2 flex justify-between items-center">
                                    <h4 class="text-sm font-medium text-gray-700">Matriz de Permisos</h4>
                                    <button type="button" onclick="toggleAllView()"
                                        class="text-xs text-indigo-600 hover:text-indigo-800">
                                        <i class="fas fa-eye mr-1"></i>Habilitar Todo Ver
                                    </button>
                                </div>

                                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0 z-10">
                                            <tr>
                                                <th
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                    Módulo</th>
                                                <th
                                                    class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                    Ver</th>
                                                <th
                                                    class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                    Crear</th>
                                                <th
                                                    class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                    Editar</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php
                                            $modules = [
                                                'dashboard' => 'Dashboard',
                                                'tesoreria' => 'Tesorería y Bancos',
                                                'colaboradores' => 'Colaboradores (Usuarios)',
                                                'agencias' => 'Agencias',
                                                'clientes' => 'Clientes',
                                                'prestamos' => 'Préstamos',
                                                'pagos' => 'Pagos',
                                                'cobradores' => 'Cobradores',
                                                'reportes' => 'Reportes',
                                                'configuracion' => 'Configuración',
                                                'seguridad' => 'Seguridad (Roles)'
                                            ];

                                            foreach ($modules as $key => $label): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                                        <?php echo $label; ?>
                                                    </td>
                                                    <td class="px-2 py-2 text-center">
                                                        <input type="checkbox" data-module="<?php echo $key; ?>"
                                                            data-action="view"
                                                            class="permiso-check rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                                    </td>
                                                    <td class="px-2 py-2 text-center">
                                                        <input type="checkbox" data-module="<?php echo $key; ?>"
                                                            data-action="create"
                                                            class="permiso-check rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                                    </td>
                                                    <td class="px-2 py-2 text-center">
                                                        <input type="checkbox" data-module="<?php echo $key; ?>"
                                                            data-action="edit"
                                                            class="permiso-check rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="saveRole()"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm btn-save">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
                <button type="button" onclick="closeModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Modal Gestión de Puestos -->
<div id="puestosModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="closePuestosModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Gestión de Puestos de Trabajo
                    </h3>
                    <button onclick="closePuestosModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Formulario -->
                    <div class="md:col-span-1 bg-gray-50 p-4 rounded-lg h-fit">
                        <h4 class="font-medium text-indigo-600 mb-3" id="puestoFormTitle">Nuevo Puesto</h4>
                        <form id="puestoForm">
                            <input type="hidden" id="id_puesto">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Nombre del Puesto *</label>
                                <input type="text" id="nombre_puesto"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select id="estado_puesto"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-2 mt-4">
                                <button type="button" onclick="closePuestosModal()"
                                    class="px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabla -->
                    <div class="md:col-span-2 overflow-y-auto max-h-[500px]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado
                                    </th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                        Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="puestosTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Ajax -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let editingRoleId = null;

    $(document).ready(function () {
        loadRoles();
    });

    function loadRoles() {
        $.get('<?php echo base_url("app/api/roles/index.php"); ?>', function (response) {
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
                // Count modules with at least 'view' access
                let activeModules = 0;
                let isReadOnly = role.permisos.readonly === true;

                for (let key in role.permisos) {
                    if (key === 'readonly') continue;

                    // Legacy support (boolean true) or Matrix support (object with view=true)
                    if (role.permisos[key] === true || (typeof role.permisos[key] === 'object' && role.permisos[key].view)) {
                        activeModules++;
                    }
                }

                if (isReadOnly) {
                    permisosHtml += '<span class="px-2 mr-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-lock mr-1"></i>Solo Lectura</span>';
                }

                permisosHtml += `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">${activeModules} Módulos Activos</span>`;
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
        $('.permiso-check').prop('checked', false); // Reset matrix
        $('#readonly_toggle').prop('checked', false);
        editingRoleId = null;
    }

    function toggleAllView() {
        // Toggle all 'view' checkboxes
        const viewCheckboxes = $('input[data-action="view"]');
        const allChecked = viewCheckboxes.filter(':checked').length === viewCheckboxes.length;
        viewCheckboxes.prop('checked', !allChecked);
    }

    function editRole(idRol) {
        $.get(`<?php echo base_url("app/api/roles/get.php"); ?>?id=${idRol}`, function (response) {
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
                            // Matrix: check specific actions
                            for (let action in val) {
                                if (val[action] === true) {
                                    $(`input[data-module="${module}"][data-action="${action}"]`).prop('checked', true);
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

        // Build Permissions Object
        const permissions = {};

        if ($('#readonly_toggle').is(':checked')) {
            permissions.readonly = true;
        }

        $('.permiso-check:checked').each(function () {
            const module = $(this).data('module');
            const action = $(this).data('action');

            if (!permissions[module]) {
                permissions[module] = {};
            }
            permissions[module][action] = true;
        });

        if (Object.keys(permissions).length === 0) {
            Swal.fire('Error', 'Debe seleccionar al menos un permiso', 'error');
            return;
        }

        const data = {
            nombre_rol: nombre,
            descripcion: descripcion,
            permisos: permissions
        };

        let url = '<?php echo base_url("app/api/roles/create.php"); ?>';
        let method = 'POST';

        if (editingRoleId) {
            url = '<?php echo base_url("app/api/roles/update.php"); ?>';
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
        $.get('<?php echo base_url("app/api/puestos/list.php"); ?>', function (response) {
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
            ? '<?php echo base_url("app/api/puestos/update.php"); ?>'
            : '<?php echo base_url("app/api/puestos/create.php"); ?>';

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

    function deletePuesto(id, nombre) {
        Swal.fire({
            title: '¿Eliminar Puesto?',
            text: `Se eliminará el puesto "${nombre}". Si está en uso, no se podrá eliminar.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo base_url("app/api/puestos/delete.php"); ?>',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ id_puesto: id }),
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Eliminado', 'El puesto ha sido eliminado', 'success');
                            loadPuestos();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error al eliminar', 'error');
                    }
                });
            }
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>