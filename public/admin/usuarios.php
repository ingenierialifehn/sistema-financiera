<?php
/**
 * Gestión de Usuarios - Admin
 */

$pageTitle = 'Gestión de Usuarios';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Usuarios Holahoalhagafasdf</h2>
            <p class="text-gray-600">Gestiona los usuarios del sistema</p>
        </div>
        <button id="btnNuevoUsuario"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Nuevo Usuario</span>
        </button>
    </div>
</div>

<!-- Búsqueda y filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" id="searchInput" placeholder="Nombre, usuario, email..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
            <select id="filterRol"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="admin">Administrador</option>
                <option value="usuario">Usuario</option>
            </select>
        </div>
        <div class="flex items-end">
            <button id="btnBuscar"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="usuariosTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        No hay usuarios registrados (Demo)
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="pagination" class="bg-gray-50 px-4 py-3 border-t border-gray-200"></div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="usuarioModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Nuevo Usuario</h3>
            <button id="btnCerrarModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="usuarioForm" class="p-6">
            <input type="hidden" id="usuarioId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" id="username" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="usuario123">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" id="email" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                    <input type="text" id="nombreCompleto" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select id="rol"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="estado"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña <span id="passwordRequired" class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelar"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Referencias a elementos
        const btnNuevo = document.getElementById('btnNuevoUsuario');
        const modal = document.getElementById('usuarioModal');
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');

        // UI functions
        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Event listeners
        if (btnNuevo) btnNuevo.addEventListener('click', openModal);
        if (btnCerrar) btnCerrar.addEventListener('click', closeModal);
        if (btnCancelar) btnCancelar.addEventListener('click', closeModal);

        // Close on click outside
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        console.log('Módulo de Usuarios inicializado');
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>