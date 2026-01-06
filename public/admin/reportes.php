<?php
/**
 * Reportes - Admin
 */

$pageTitle = 'Reportes';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Reportes</h2>
    <p class="text-gray-600">Visualiza y analiza los datos del sistema</p>
</div>

<!-- Tabs de Reportes -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
            <button onclick="showReport('resumen')" id="tab-resumen" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-chart-pie"></i> Resumen
            </button>
            <button onclick="showReport('cobros')" id="tab-cobros" class="tab-button active px-6 py-3 text-sm font-medium text-indigo-600 border-b-2 border-indigo-600">
                <i class="fas fa-money-bill-wave"></i> Cobros
            </button>
            <button onclick="showReport('mora')" id="tab-mora" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-exclamation-triangle"></i> Mora
            </button>
            <button onclick="showReport('ingresos')" id="tab-ingresos" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-chart-line"></i> Ingresos
            </button>
            <button onclick="showReport('cartera')" id="tab-cartera" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-wallet"></i> Cartera
            </button>
            <button onclick="showReport('cobradores')" id="tab-cobradores" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700">
                <i class="fas fa-user-tie"></i> Cobradores
            </button>
        </nav>
    </div>
</div>

<!-- Filtros -->
<div id="filtersContainer" class="bg-white rounded-lg shadow p-4 mb-6">
    <!-- Los filtros se cargarán dinámicamente según el reporte -->
</div>

<!-- Estadísticas -->
<div id="statsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Se cargarán dinámicamente -->
</div>

<!-- Contenido del Reporte -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <div id="reportContent">
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-chart-bar text-4xl mb-4"></i>
                <p>Selecciona un reporte para visualizar</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/reportes.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>

