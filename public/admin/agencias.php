<?php
/**
 * Gestión de Agencias - Admin
 */

$pageTitle = 'Gestión de Agencias';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('agencias');
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Agencias</h2>
            <p class="text-gray-600">Administra las sucursales y sus ubicaciones</p>
        </div>
        <?php if (tienePermiso('agencias', 'crear')): ?>
            <button id="btnNuevaAgencia"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-plus"></i>
                <span>Nueva Agencia</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tabla de Agencias -->
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Agencia
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Ubicación / Contacto
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Colaboradores
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="agenciasTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-indigo-500 mb-3"></i>
                            <span>Cargando agencias...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Maestro-Detalle -->
<div id="agenciaModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden transform transition-all scale-100">
        <!-- Header Modal -->
        <div
            class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-4 flex justify-between items-center shadow-md">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-building"></i>
                <span id="modalTitle">Nueva Agencia</span>
            </h3>
            <button id="btnCerrarModal"
                class="text-white hover:text-indigo-200 transition bg-white/10 hover:bg-white/20 rounded-full p-2 w-8 h-8 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Form -->
        <div class="p-6 bg-gray-50">
            <form id="agenciaForm" class="space-y-4">
                <input type="hidden" id="agenciaId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Agencia *</label>
                    <input type="text" id="nombreAgencia" required placeholder="Ej: Agencia Central"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                        <input type="text" id="ciudad" placeholder="Ej: Tegucigalpa"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" id="telefonoAgencia" placeholder="Ej: 2233-4455"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Completa</label>
                    <textarea id="direccion" rows="3" placeholder="Ej: Col. Las Lomas, Ave. Principal..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="estado"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="Activa">Activa</option>
                        <option value="Inactiva">Inactiva</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Footer Modal -->
        <div class="bg-gray-100 px-6 py-4 flex justify-end space-x-3 border-t">
            <button type="button" id="btnCancelar"
                class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition shadow-sm">
                Cancelar
            </button>
            <?php if (tienePermiso('agencias', 'crear') || tienePermiso('agencias', 'editar')): ?>
                <button type="submit" form="agenciaForm"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition shadow-md flex items-center">
                    <i class="fas fa-save mr-2"></i> Guardar
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/agencias.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>