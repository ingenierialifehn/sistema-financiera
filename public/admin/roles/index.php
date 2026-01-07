<?php
/**
 * Página: Gestión de Roles - Versión con Permisos Granulares Completos
 */
$pageTitle = 'Roles y Permisos';
require_once __DIR__ . '/../includes/layout.php';

// Redirigir si no tiene permiso
if (!Auth::hasPermission('seguridad')) {
    header('Location: ' . base_url('public/admin/dashboard.php'));
    exit;
}

// Cargar permisos detallados
$detailedPermissions = require __DIR__ . '/../../../app/config/permissions.php';

// Organizar por categorías
$moduleCategories = [
    'Administración' => ['dashboard', 'tesoreria', 'agencias', 'colaboradores', 'seguridad'],
    'Operaciones Diarias' => ['operaciones', 'caja', 'boveda'],
    'Gestión de Clientes y Créditos' => ['clientes', 'prestamos', 'garantias', 'referencias', 'pagos', 'cobrador'],
    'Reportes y Configuración' => ['reportes', 'configuracion'],
];
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-shield-alt mr-2"></i>Roles y Permisos Detallados
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

    <!-- Alerta Informativa -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Control Granular:</strong> Ahora puedes controlar TODOS los botones y acciones específicas
                    de cada módulo.
                    Cada permiso representa un botón o funcionalidad específica del sistema.
                </p>
            </div>
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
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl w-full">
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
                                    <h4 class="text-sm font-medium text-gray-700">
                                        <i class="fas fa-list-check mr-2"></i>Permisos Detallados por Módulo
                                    </h4>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="toggleAllPermissions()"
                                            class="text-xs text-green-600 hover:text-green-800">
                                            <i class="fas fa-check-double mr-1"></i>Seleccionar Todos
                                        </button>
                                        <button type="button" onclick="clearAllPermissions()"
                                            class="text-xs text-red-600 hover:text-red-800">
                                            <i class="fas fa-times mr-1"></i>Limpiar Todos
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                                    <?php foreach ($moduleCategories as $category => $modules): ?>
                                        <!-- Categoría Header -->
                                        <div
                                            class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white px-4 py-2 sticky top-0 z-10">
                                            <h5 class="font-bold text-sm">
                                                <i class="fas fa-folder-open mr-2"></i><?php echo $category; ?>
                                            </h5>
                                        </div>

                                        <?php foreach ($modules as $moduleKey): ?>
                                            <?php if (isset($detailedPermissions[$moduleKey])): ?>
                                                <?php $module = $detailedPermissions[$moduleKey]; ?>

                                                <!-- Módulo -->
                                                <div class="border-b border-gray-200">
                                                    <div class="bg-gray-50 px-4 py-2 cursor-pointer hover:bg-gray-100"
                                                        onclick="toggleModule('<?php echo $moduleKey; ?>')">
                                                        <div class="flex justify-between items-center">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-chevron-right mr-2 text-gray-400 transition-transform module-icon"
                                                                    id="icon-<?php echo $moduleKey; ?>"></i>
                                                                <span class="font-medium text-gray-900">
                                                                    <?php echo $module['label']; ?>
                                                                </span>
                                                                <span class="ml-2 text-xs text-gray-500">
                                                                    (<?php echo count($module['permissions']); ?> permisos)
                                                                </span>
                                                            </div>
                                                            <button type="button"
                                                                onclick="event.stopPropagation(); toggleModuleAll('<?php echo $moduleKey; ?>')"
                                                                class="text-xs text-indigo-600 hover:text-indigo-800">
                                                                <i class="fas fa-check-square mr-1"></i>Todos
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Permisos del Módulo -->
                                                    <div class="hidden px-4 py-3 bg-white" id="perms-<?php echo $moduleKey; ?>">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                                            <?php foreach ($module['permissions'] as $permKey => $permLabel): ?>
                                                                <label
                                                                    class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                                    <input type="checkbox" data-module="<?php echo $moduleKey; ?>"
                                                                        data-permission="<?php echo $permKey; ?>"
                                                                        class="permiso-check rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                                                    <span class="text-sm text-gray-700"><?php echo $permLabel; ?></span>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
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
    // Función para expandir/contraer módulos
    function toggleModule(moduleKey) {
        const permsDiv = document.getElementById('perms-' + moduleKey);
        const icon = document.getElementById('icon-' + moduleKey);

        if (permsDiv.classList.contains('hidden')) {
            permsDiv.classList.remove('hidden');
            icon.classList.add('rotate-90');
        } else {
            permsDiv.classList.add('hidden');
            icon.classList.remove('rotate-90');
        }
    }

    // Función para seleccionar/deseleccionar todos los permisos de un módulo
    function toggleModuleAll(moduleKey) {
        const checkboxes = document.querySelectorAll(`input[data-module="${moduleKey}"]`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
    }

    // Función para seleccionar todos los permisos
    function toggleAllPermissions() {
        const checkboxes = document.querySelectorAll('.permiso-check');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
    }

    // Función para limpiar todos los permisos
    function clearAllPermissions() {
        document.querySelectorAll('.permiso-check').forEach(cb => {
            cb.checked = false;
        });
    }
</script>

<script src="<?php echo base_url('public/admin/assets/js/roles_detailed.js?v=' . time()); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>