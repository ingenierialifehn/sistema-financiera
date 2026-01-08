<?php
/**
 * Dashboard Gerencial
 * Vista consolidada para la Gerencia
 */
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
Auth::checkSession();
$user = Auth::getCurrentUser();

// Simple Access Check (Optional, adjust as needed)
// if (!in_array($user['rol_nombre'], ['Administrador', 'Gerente', 'Gerente General'])) {
//     header('Location: ' . BASE_URL . '/public/admin/dashboard.php');
//     exit;
// }

$pageTitle = "Dashboard Gerencial";
require_once __DIR__ . '/includes/layout.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dashboard Gerencial</h1>
            <p class="text-gray-600">Visión global del rendimiento financiero</p>
        </div>

        <!-- Agency Filter -->
        <div class="flex items-center bg-white p-2 rounded-lg shadow border border-gray-200">
            <label class="text-sm font-bold text-gray-500 mr-3 uppercase"><i class="fas fa-building mr-1"></i>
                Agencias</label>
            <select id="filtroAgencia" onchange="loadStats()"
                class="border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="todas">Todas las Agencias</option>
                <!-- Filled via JS -->
            </select>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Capital Activo -->
        <div
            class="bg-white rounded-xl shadow-lg border-l-4 border-blue-500 p-6 transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Capital en la Calle</p>
                    <h3 class="text-2xl font-extrabold text-blue-700 mt-2" id="kpi_capital">Cargando...</h3>
                    <p class="text-xs text-blue-400 mt-1">Saldo pendiente de cobro</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-full text-blue-500">
                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Intereses Ganados -->
        <div
            class="bg-white rounded-xl shadow-lg border-l-4 border-green-500 p-6 transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Intereses Ganados</p>
                    <h3 class="text-2xl font-extrabold text-green-700 mt-2" id="kpi_interes">Cargando...</h3>
                    <p class="text-xs text-green-400 mt-1">Total acumulado histórico</p>
                </div>
                <div class="p-3 bg-green-50 rounded-full text-green-500">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Mora Total -->
        <div
            class="bg-white rounded-xl shadow-lg border-l-4 border-red-500 p-6 transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Mora Total (B-E)</p>
                    <h3 class="text-2xl font-extrabold text-red-700 mt-2" id="kpi_mora">Cargando...</h3>
                    <p class="text-xs text-red-400 mt-1">Cartera con atraso > 0 días</p>
                </div>
                <div class="p-3 bg-red-50 rounded-full text-red-500">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Generación de Reportes -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-print mr-2 text-indigo-600"></i> Reportes Operativos
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Cierre Diario -->
            <div class="border rounded-lg p-4 hover:bg-gray-50 transition border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-700">Cierre de Caja Diario</h4>
                        <p class="text-sm text-gray-500">Resumen de ingresos y egresos del día por agencia.</p>
                    </div>
                    <button onclick="generarReporte('cierre_diario')"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm font-bold flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Generar
                    </button>
                </div>
            </div>

            <!-- Estado de Cartera -->
            <div class="border rounded-lg p-4 hover:bg-gray-50 transition border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-700">Estado de Cartera</h4>
                        <p class="text-sm text-gray-500">Listado de clientes con saldo y categoría de riesgo.</p>
                    </div>
                    <button onclick="generarReporte('estado_cartera')"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded shadow text-sm font-bold flex items-center">
                        <i class="fas fa-list-ol mr-2"></i> Generar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        loadAgencias();
        loadStats();
    });

    async function loadAgencias() {
        try {
            const agSelect = document.getElementById('filtroAgencia');
            const res = await fetch(`${BASE_URL}/app/api/agencias/list.php`);
            const data = await res.json();

            if (data.success) {
                data.data.forEach(ag => {
                    const opt = document.createElement('option');
                    opt.value = ag.id_agencia; // field name might be id_agencia or id? Checking 'list.php' step 5.
                    opt.innerText = ag.nombre_agencia;
                    agSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error("Error loading agencies", e);
        }
    }

    async function loadStats() {
        const agenciaId = document.getElementById('filtroAgencia').value;

        // Set Loading States
        ['kpi_capital', 'kpi_interes', 'kpi_mora'].forEach(id => {
            document.getElementById(id).classList.add('animate-pulse');
            document.getElementById(id).style.opacity = '0.5';
        });

        try {
            const res = await fetch(`${BASE_URL}/app/api/reports/global_stats.php?agencia_id=${agenciaId}`);
            const data = await res.json();

            if (data.success) {
                updateKPI('kpi_capital', data.data.capital_en_calle);
                updateKPI('kpi_interes', data.data.intereses_ganados);
                updateKPI('kpi_mora', data.data.mora_total);
            }
        } catch (e) {
            console.error("Error loading stats", e);
        } finally {
            ['kpi_capital', 'kpi_interes', 'kpi_mora'].forEach(id => {
                document.getElementById(id).classList.remove('animate-pulse');
                document.getElementById(id).style.opacity = '1';
            });
        }
    }

    function updateKPI(id, value) {
        document.getElementById(id).innerText = 'L ' + parseFloat(value).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function generarReporte(tipo) {
        const agenciaId = document.getElementById('filtroAgencia').value;
        const url = `${BASE_URL}/public/admin/reportes/${tipo}.php?agencia_id=${agenciaId}`;
        window.open(url, '_blank');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>