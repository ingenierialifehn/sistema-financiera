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

// Cargar permisos detallados ordenados (Top-Down)
$detailedPermissions = require __DIR__ . '/../../../app/config/permissions.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">
                Roles y Permisos
            </h1>
            <p class="text-gray-500 mt-1">Gestión granular de acceso al sistema</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openPuestosModal()"
                class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-2 px-4 rounded-lg shadow-sm transition flex items-center">
                <i class="fas fa-briefcase mr-2 text-gray-500"></i>Puestos
            </button>
            <button onclick="openModal()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-lg hover:shadow-xl transition flex items-center transform hover:-translate-y-0.5">
                <i class="fas fa-plus mr-2"></i>Nuevo Rol
            </button>
        </div>
    </div>

    <!-- Lista de Roles (Cards Grid para vista moderna, o Tabla mejorada) -->
    <!-- Usaremos Tabla Mejorada para mantener densidad de información -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                        Descripción</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Permisos (Resumen)
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="rolesTableBody" class="bg-white divide-y divide-gray-100">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex justify-center items-center">
                            <i class="fas fa-circle-notch fa-spin mr-3 text-indigo-500"></i>Cargando roles...
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar Rol (Diseño Amplio) -->
<div id="roleModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <!-- Backdrop estático -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Modal Content -->
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl transform transition-all flex flex-col max-h-[90vh]">
            
            <!-- Header Fijo -->
            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-2xl">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900" id="modal-title">Configuración de Rol</h3>
                    <p class="text-sm text-gray-500 mt-1">Defina los accesos y restricciones para este perfil.</p>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body Scrollable -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <form id="roleForm" class="space-y-8">
                    <input type="hidden" id="id_rol" name="id_rol">

                    <!-- Info Básica -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Rol *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-user-tag"></i>
                                </span>
                                <input type="text" name="nombre_rol" id="nombre_rol" required placeholder="Ej: Gestor de Cobranza"
                                    class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="1" placeholder="Breve descripción de responsabilidades..."
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <!-- Toggle Solo Lectura -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 flex items-center justify-between">
                        <div class="flex items-center">
                             <div class="bg-blue-100 p-3 rounded-full text-blue-600 mr-4">
                                <i class="fas fa-eye text-xl"></i>
                             </div>
                             <div>
                                <h4 class="font-bold text-gray-800">Modo Solo Lectura Global</h4>
                                <p class="text-sm text-gray-600">Si se activa, este rol podrá ver los módulos permitidos pero <strong>no podrá realizar modificaciones</strong> (guardar, editar, eliminar).</p>
                             </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="readonly_toggle" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Separator -->
                    <div class="border-t border-gray-100"></div>

                    <!-- Gestión de Permisos por Módulo -->
                    <div>
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-lg font-bold text-gray-800 flex items-center">
                                <i class="fas fa-layer-group text-indigo-500 mr-2"></i> Accesos por Módulo
                            </h4>
                            <div class="flex space-x-2">
                                <button type="button" onclick="toggleAllPermissions()" class="text-xs font-semibold px-3 py-1 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition border border-green-200">
                                    <i class="fas fa-check-double mr-1"></i> Seleccionar Todo
                                </button>
                                <button type="button" onclick="clearAllPermissions()" class="text-xs font-semibold px-3 py-1 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition border border-red-200">
                                    <i class="fas fa-trash-alt mr-1"></i> Limpiar Todo
                                </button>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($detailedPermissions as $moduleKey => $module): ?>
                                <!-- Card Módulo -->
                                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow duration-300 module-card">
                                    <!-- Header Módulo -->
                                    <div class="bg-gray-50 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between cursor-pointer" onclick="toggleModuleBody('<?php echo $moduleKey; ?>')">
                                        <div class="flex items-center space-x-4">
                                             <div class="h-10 w-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
                                                 <?php echo strtoupper(substr($moduleKey, 0, 1)); ?>
                                             </div>
                                             <div>
                                                 <h5 class="text-base font-bold text-gray-900"><?php echo $module['label']; ?></h5>
                                                 <?php if(isset($module['description'])): ?>
                                                    <p class="text-xs text-gray-500 truncate max-w-md"><?php echo $module['description']; ?></p>
                                                 <?php endif; ?>
                                             </div>
                                        </div>
                                        
                                        <div class="flex items-center mt-3 md:mt-0 space-x-6">
                                            <!-- Counts -->
                                            <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded border border-gray-200 shadow-sm" id="count-<?php echo $moduleKey; ?>">0 seleccionados</span>
                                            
                                            <!-- Botón Toggle Todo Módulo -->
                                            <div class="flex items-center" onclick="event.stopPropagation()">
                                                <span class="mr-3 text-sm font-medium text-gray-700">Acceso Módulo</span>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" onchange="toggleModuleAll('<?php echo $moduleKey; ?>')" id="master-check-<?php echo $moduleKey; ?>" class="sr-only peer module-master-checkbox">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                                </label>
                                            </div>
                                            
                                            <!-- Chevron -->
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="chevron-<?php echo $moduleKey; ?>"></i>
                                        </div>
                                    </div>

                                    <!-- Body Permisos -->
                                    <div class="px-6 py-6 border-t border-gray-100 hidden md:block" id="body-<?php echo $moduleKey; ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <?php foreach ($module['permissions'] as $permKey => $permLabel): ?>
                                                <label class="flex items-start p-3 rounded-lg border border-gray-100 hover:bg-gray-50 hover:border-gray-200 cursor-pointer transition-colors group">
                                                    <div class="flex items-center h-5">
                                                        <input type="checkbox" 
                                                               data-module="<?php echo $moduleKey; ?>"
                                                               data-permission="<?php echo $permKey; ?>"
                                                               onchange="updateModuleCount('<?php echo $moduleKey; ?>')"
                                                               class="permiso-check w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mt-0.5">
                                                    </div>
                                                    <div class="ml-3 text-sm">
                                                        <span class="font-medium text-gray-700 group-hover:text-gray-900"><?php echo str_replace('(', '<span class="block text-xs text-gray-500 font-normal mt-0.5">', str_replace(')', '</span>', $permLabel)); ?></span>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Footer Fijo -->
            <div class="px-8 py-5 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl flex justify-end space-x-3">
                 <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-white hover:shadow-sm transition bg-white">
                    Cancelar
                 </button>
                 <button type="button" onclick="saveRole()" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 hover:shadow-lg transition flex items-center btn-save">
                    <i class="fas fa-save mr-2"></i> Guardar Cambios
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