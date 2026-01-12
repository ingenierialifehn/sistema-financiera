<?php
/**
 * Verificación de Campo
 * Módulo para ver y autorizar préstamos en etapa de verificación
 */

$pageTitle = 'Verificación de Campo';

require_once __DIR__ . '/../auth_check.php';

if (!Auth::hasPermission('verificacion_campo.view')) {
    header('Location: ' . base_url('public/admin/dashboard.php'));
    exit;
}

require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Verificación de Campo</h2>
            <p class="text-gray-600">Revisión y autorización de solicitudes de crédito</p>
        </div>
        <button id="btnRefresh" class="text-indigo-600 hover:text-indigo-800 transition">
            <i class="fas fa-sync-alt"></i> Actualizar lista
        </button>
    </div>
</div>

<!-- Búsqueda -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" id="searchInput" placeholder="Número, cliente..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-end">
            <!-- Placeholder for filters if needed -->
        </div>
    </div>
</div>

<!-- Tabla de préstamos pendientes de verificación -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha
                        Solicitud</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto
                        Solicitado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asesor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="verificacionTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="pagination" class="bg-gray-50 px-4 py-3 border-t border-gray-200"></div>
</div>

<script>
    const API_BASE_URL = '<?php echo base_url("app/api"); ?>';
    const VIEWS_BASE_URL = '<?php echo base_url("public/admin"); ?>';
</script>
<script src="<?php echo base_url('public/admin/assets/js/verificacion_campo.js'); ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>