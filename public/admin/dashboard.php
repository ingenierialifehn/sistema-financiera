<?php
/**
 * Dashboard de Administrador
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('dashboard');
require_once __DIR__ . '/includes/layout.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-600">Bienvenido al panel de administración</p>
</div>

<!-- Tarjetas de Métricas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8" id="metricsContainer">
    <!-- Widget: Préstamos Activos -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Préstamos Activos</p>
                <p class="text-3xl font-bold text-gray-800" id="total_prestamos_activos">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-hand-holding-usd text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Widget: Cartera Total -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Cartera Total</p>
                <p class="text-3xl font-bold text-gray-800" id="cartera_total">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-wallet text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Widget: Cobros Hoy -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Cobros Hoy</p>
                <p class="text-3xl font-bold text-gray-800" id="cobros_hoy">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <i class="fas fa-money-bill-wave text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Widget: Cuotas Vencidas -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Cuotas Vencidas</p>
                <p class="text-3xl font-bold text-gray-800" id="cuotas_vencidas">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Widget: Cobradores Activos -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Cobradores Activos</p>
                <p class="text-3xl font-bold text-gray-800" id="cobradores_activos">
                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                </p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <i class="fas fa-users text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gráfica de Cobros -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Cobros por Día (Últimos 30 días)</h3>
        <div class="flex space-x-2">
            <button onclick="loadChart(7)" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">7
                días</button>
            <button onclick="loadChart(30)"
                class="px-3 py-1 text-sm bg-indigo-600 text-white hover:bg-indigo-700 rounded">30 días</button>
            <button onclick="loadChart(90)" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">90
                días</button>
        </div>
    </div>
    <canvas id="paymentsChart" height="80"></canvas>
</div>

<!-- Tabla de Últimos Pagos -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Últimos Pagos</h3>
        <button onclick="loadLatestPayments()" class="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Préstamo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cobrador
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Comprobante</th>
                </tr>
            </thead>
            <tbody id="paymentsTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/dashboard.js"></script>
<script>
    // Inicializar dashboard cuando el DOM esté listo
    $(document).ready(function () {
        loadSummary();
        loadChart(30);
        loadLatestPayments();

        // Auto-refresh cada 5 minutos
        setInterval(function () {
            loadSummary();
            loadLatestPayments();
        }, 300000);
    });
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>