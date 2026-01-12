/**
 * Roles y Permisos Granulares - Logic v2 (Modern UI)
 */

document.addEventListener('DOMContentLoaded', function () {
    loadRoles();
    loadPuestos();
});

// Estado global
let roles = [];
let editingRoleId = null;
const API_URL = `${BASE_URL}/app/api/roles`;

// Cargar roles desde API
async function loadRoles() {
    try {
        const response = await fetch(`${API_URL}/index.php`);
        const result = await response.json();

        if (result.success) {
            roles = result.data;
            renderRolesTable();
        } else {
            console.error(result.message);
            Swal.fire('Error', 'No se pudieron cargar los roles', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        // Swal.fire('Error', 'Error de conexión', 'error');
    }
}

// Renderizar tabla
function renderRolesTable() {
    const tbody = document.getElementById('rolesTableBody');
    tbody.innerHTML = '';

    if (!roles || roles.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No hay roles definidos</td></tr>`;
        return;
    }

    roles.forEach(role => {
        // Calcular resumen permisos
        let permisosHtml = '';
        let moduleCount = 0;

        if (role.permisos) {
            if (role.permisos.todos) {
                permisosHtml = '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">Acceso Total</span>';
            } else {
                // Contar módulos con acceso
                Object.keys(role.permisos).forEach(key => {
                    const val = role.permisos[key];
                    if (key === 'readonly') return; // no contar readonly como módulo

                    if (val === true) {
                        moduleCount++;
                    } else if (typeof val === 'object') {
                        if (Object.values(val).some(v => v === true)) {
                            moduleCount++;
                        }
                    }
                });

                if (moduleCount === 0) {
                    permisosHtml = '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Sin Acceso</span>';
                } else {
                    permisosHtml = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">${moduleCount} Módulos Activos</span>`;
                }
            }
        } else {
            permisosHtml = '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Sin Acceso</span>';
        }

        let actionsHtml = '';

        // Admin protection
        if (role.nombre_rol === 'Administrador') {
            actionsHtml = `
                <button onclick="editRole(${role.id_rol})" class="text-indigo-600 hover:text-indigo-900 mx-2 btn-edit" title="Editar">
                    <i class="fas fa-edit"></i> Editar
                </button>
            `;
        } else {
            actionsHtml = `
                <button onclick="editRole(${role.id_rol})" class="text-indigo-600 hover:text-indigo-900 mx-2 btn-edit" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteRole(${role.id_rol})" class="text-red-600 hover:text-red-900 mx-2 btn-delete" title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        }

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition-colors border-b border-gray-50';
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg shadow-sm">
                        ${role.nombre_rol.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-bold text-gray-900">${role.nombre_rol}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-500 max-w-xs truncate" title="${role.descripcion || ''}">${role.descripcion || '-'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${permisosHtml}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${role.estado === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${role.estado}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                ${actionsHtml}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// UI Functions for Modal
function openModal() {
    editingRoleId = null;
    document.getElementById('modal-title').textContent = 'Nuevo Rol';
    document.getElementById('roleForm').reset();
    document.getElementById('id_rol').value = '';

    // Reset UI
    document.querySelectorAll('.permiso-check').forEach(cb => cb.checked = false);
    document.querySelectorAll('[id^=master-check-]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
    document.querySelectorAll('[id^=count-]').forEach(span => span.textContent = '0 seleccionados');
    document.getElementById('readonly_toggle').checked = false;

    // Reset visual toggle states (close all)
    document.querySelectorAll('[id^=body-]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^=chevron-]').forEach(el => el.classList.remove('rotate-180'));

    document.getElementById('roleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('roleModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function toggleModuleBody(moduleKey) {
    const body = document.getElementById('body-' + moduleKey);
    const chevron = document.getElementById('chevron-' + moduleKey);

    if (body.classList.contains('hidden')) {
        body.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        body.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}

function updateModuleCount(moduleKey) {
    const checkboxes = document.querySelectorAll(`input[data-module="${moduleKey}"]`);
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;

    const countSpan = document.getElementById(`count-${moduleKey}`);
    if (countSpan) countSpan.textContent = `${checkedCount} de ${totalCount}`;

    // Update master check
    const masterCheck = document.getElementById(`master-check-${moduleKey}`);
    if (masterCheck) {
        if (checkedCount === totalCount && totalCount > 0) {
            masterCheck.checked = true;
            masterCheck.indeterminate = false;
        } else if (checkedCount === 0) {
            masterCheck.checked = false;
            masterCheck.indeterminate = false;
        } else {
            masterCheck.checked = false;
            masterCheck.indeterminate = true;
        }
    }
}

function toggleModuleAll(moduleKey) {
    const masterCheck = document.getElementById(`master-check-${moduleKey}`);
    const isChecked = masterCheck.checked;

    const checkboxes = document.querySelectorAll(`input[data-module="${moduleKey}"]`);
    checkboxes.forEach(cb => cb.checked = isChecked);

    updateModuleCount(moduleKey);
}

function toggleAllPermissions() {
    const checkboxes = document.querySelectorAll('.permiso-check');
    checkboxes.forEach(cb => cb.checked = true);

    // Update all counts
    document.querySelectorAll('[id^=master-check-]').forEach(cb => {
        const key = cb.id.replace('master-check-', '');
        updateModuleCount(key);
    });
}

function clearAllPermissions() {
    const checkboxes = document.querySelectorAll('.permiso-check');
    checkboxes.forEach(cb => cb.checked = false);

    // Update all counts
    document.querySelectorAll('[id^=master-check-]').forEach(cb => {
        const key = cb.id.replace('master-check-', '');
        updateModuleCount(key);
    });
}

// Editar Rol
async function editRole(id) {
    editingRoleId = id;
    const role = roles.find(r => r.id_rol == id);
    if (!role) return;

    document.getElementById('modal-title').textContent = 'Editar Rol: ' + role.nombre_rol;
    document.getElementById('id_rol').value = role.id_rol;
    document.getElementById('nombre_rol').value = role.nombre_rol;
    document.getElementById('descripcion').value = role.descripcion || '';

    // Reset Permissions
    const allChecks = document.querySelectorAll('.permiso-check');
    allChecks.forEach(cb => cb.checked = false);
    document.querySelectorAll('[id^=master-check-]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });

    // Cargar permisos
    if (role.permisos) {
        // Handle "readonly"
        document.getElementById('readonly_toggle').checked = !!role.permisos.readonly;

        if (role.permisos.todos) {
            allChecks.forEach(cb => cb.checked = true);
        } else {
            const permissions = role.permisos;
            Object.keys(permissions).forEach(moduleKey => {
                const modulePayload = permissions[moduleKey];

                if (modulePayload === true) {
                    // Check all in this module
                    const modChecks = document.querySelectorAll(`input[data-module="${moduleKey}"]`);
                    modChecks.forEach(cb => cb.checked = true);
                } else if (typeof modulePayload === 'object') {
                    // Specific actions
                    Object.keys(modulePayload).forEach(actionKey => {
                        if (modulePayload[actionKey] === true) {
                            const cb = document.querySelector(`input[data-module="${moduleKey}"][data-permission="${actionKey}"]`);
                            if (cb) cb.checked = true;
                        }
                    });
                }
            });
        }
    }

    // Update Counts
    document.querySelectorAll('[id^=master-check-]').forEach(cb => {
        const key = cb.id.replace('master-check-', '');
        updateModuleCount(key);
    });

    // Expand modules with permissions? Optional.
    // document.querySelectorAll('[id^=body-]').forEach(b => b.classList.add('hidden'));

    document.getElementById('roleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Guardar Rol
async function saveRole() {
    const idRol = document.getElementById('id_rol').value;
    const nombreRol = document.getElementById('nombre_rol').value;
    const descripcion = document.getElementById('descripcion').value;
    const isReadOnly = document.getElementById('readonly_toggle').checked;

    if (!nombreRol || nombreRol.length < 3) {
        Swal.fire('Error', 'El nombre del rol es requerido', 'warning');
        return;
    }

    const permissions = {};
    if (isReadOnly) permissions.readonly = true;

    const checkboxes = document.querySelectorAll('.permiso-check:checked');
    checkboxes.forEach(cb => {
        const module = cb.dataset.module;
        const action = cb.dataset.permission;

        if (!permissions[module]) permissions[module] = {};
        permissions[module][action] = true;
    });

    const payload = {
        nombre_rol: nombreRol,
        descripcion: descripcion,
        permisos: permissions,
        id_rol: idRol ? parseInt(idRol) : null
    };

    const endpoint = idRol ? 'update.php' : 'create.php';
    const method = idRol ? 'PUT' : 'POST';

    try {
        // Show loading
        Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

        const response = await fetch(`${API_URL}/${endpoint}`, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Éxito', result.message, 'success');
            closeModal();
            loadRoles();
        } else {
            Swal.fire('Error', result.message || 'Error al guardar', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}

// Eliminar Rol
async function deleteRole(id) {
    const result = await Swal.fire({
        title: '¿Eliminar Rol?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    });

    if (result.isConfirmed) {
        // Placeholder for delete logic if not implemented
        Swal.fire('Info', 'Funcionalidad de eliminar segura pendiente implementación backend.', 'info');
    }
}

// --- PUESTOS (Placeholder) ---
function loadPuestos() { /* ... */ }
function openPuestosModal() {
    Swal.fire('Puestos', 'Módulo de Puestos disponible en gestión separada.', 'info');
}
