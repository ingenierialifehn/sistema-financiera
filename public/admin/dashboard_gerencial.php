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

        <!-- Capital Sano -->
        <div
            class="bg-white rounded-xl shadow-lg border-l-4 border-green-500 p-6 transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Capital Sano</p>
                    <h3 class="text-2xl font-extrabold text-green-700 mt-2" id="kpi_capital_sano">Cargando...</h3>
                    <p class="text-xs text-green-400 mt-1">Cartera al día</p>
                </div>
                <div class="p-3 bg-green-50 rounded-full text-green-500">
                    <i class="fas fa-check-circle text-2xl"></i>
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

    <!-- Resumen Operativo Diario (Nuevo) -->
    <div class="mb-10">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-cash-register text-indigo-600 mr-2"></i> Resumen Operativo Diario
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- 1. Recaudo Total -->
            <div
                class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform hover:scale-105 transition duration-300">
                <div class="relative z-10">
                    <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-2">Total Recaudado Hoy</p>
                    <h3 class="text-3xl font-extrabold" id="op_recaudo">L ...</h3>
                    <p class="text-xs text-indigo-200 mt-1">Suma de todos los asesores</p>
                </div>
                <i
                    class="fas fa-hand-holding-usd absolute right-4 bottom-4 text-indigo-400 text-opacity-30 text-5xl"></i>
            </div>

            <!-- 2. Efectivo Desembolsadores -->
            <div
                class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform hover:scale-105 transition duration-300">
                <div class="relative z-10">
                    <p class="text-orange-100 text-xs font-bold uppercase tracking-wider mb-2">Efectivo en Ruta</p>
                    <h3 class="text-3xl font-extrabold" id="op_oficiales">L ...</h3>
                    <p class="text-xs text-orange-200 mt-1">Oficiales de Desembolso</p>
                </div>
                <i class="fas fa-motorcycle absolute right-4 bottom-4 text-orange-400 text-opacity-30 text-5xl"></i>
            </div>

            <!-- 3. Cajas y Bóvedas -->
            <div
                class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform hover:scale-105 transition duration-300">
                <div class="relative z-10">
                    <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-2">Disponibilidad Total</p>
                    <h3 class="text-3xl font-extrabold" id="op_bovedas">L ...</h3>
                    <p class="text-xs text-emerald-200 mt-1">Cajas Agencias + Bóvedas Bancos</p>
                </div>
                <i class="fas fa-university absolute right-4 bottom-4 text-emerald-400 text-opacity-30 text-5xl"></i>
            </div>

            <!-- 4. Agencias -->
            <div
                class="bg-white rounded-xl shadow-lg border-l-4 border-gray-500 p-6 relative overflow-hidden flex flex-col justify-between transform hover:scale-105 transition duration-300">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Agencias Activas</p>
                    <div class="flex items-baseline">
                        <h3 class="text-3xl font-extrabold text-gray-800 mr-2" id="op_agencias">0</h3>
                        <span class="text-gray-500 font-medium" id="op_agencias_total">de 0 Agencias</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1" id="op_agencias_info">...</p>
                </div>
                <button onclick="verDetalleAgencias()"
                    class="mt-4 w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded flex items-center justify-center transition">
                    <i class="fas fa-eye mr-2"></i> Ver Detalle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Agencias -->
<div id="modalAgencias" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Estado de Agencias Hoy</h3>
                <button onclick="document.getElementById('modalAgencias').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="listaAgencias" class="mt-2 space-y-3">
                <!-- JS Fill -->
            </div>
            <div class="mt-4 text-right">
                <button onclick="document.getElementById('modalAgencias').classList.add('hidden')"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded font-bold">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // BASE_URL is already defined in header


    // Init
    document.addEventListener('DOMContentLoaded', () => {
        loadAgencias();
        loadStats();
        loadOperationalStats();
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
        ['kpi_capital', 'kpi_capital_sano', 'kpi_mora'].forEach(id => {
            document.getElementById(id).classList.add('animate-pulse');
            document.getElementById(id).style.opacity = '0.5';
        });

        try {
            const res = await fetch(`${BASE_URL}/app/api/reports/global_stats.php?agencia_id=${agenciaId}`);
            const data = await res.json();

            if (data.success) {
                updateKPI('kpi_capital', data.data.capital_en_calle);
                updateKPI('kpi_capital_sano', data.data.capital_sano);
                updateKPI('kpi_mora', data.data.mora_total);
            }
        } catch (e) {
            console.error("Error loading stats", e);
        } finally {
            ['kpi_capital', 'kpi_capital_sano', 'kpi_mora'].forEach(id => {
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

    async function loadOperationalStats() {
        try {
            const res = await fetch(`${BASE_URL}/app/api/reports/operational_stats.php`);
            const json = await res.json();

            if (json.success) {
                const d = json.data;

                // Animate Numbers
                document.getElementById('op_recaudo').innerText = fmtMoney(d.recaudo_total);
                document.getElementById('op_oficiales').innerText = fmtMoney(d.efectivo_oficiales);
                document.getElementById('op_bovedas').innerText = fmtMoney(d.disponibilidad_total);

                // Agencias
                document.getElementById('op_agencias').innerText = d.agencias_activas;
                document.getElementById('op_agencias_total').innerText = `de ${d.agencias_total} Agencias`;

                // Info text
                const lacking = d.agencias_activas - d.agencias_cerradas;
                document.getElementById('op_agencias_info').innerText = `${d.agencias_cerradas} Cerradas / ${d.agencias_total - d.agencias_activas} Sin Abrir`;

                // Guarda detalle para el modal
                window.agenciasDetalle = d.detalle_agencias;
            }
        } catch (e) {
            console.error("Error operational stats", e);
        }
    }

    function fmtMoney(amount) {
        return 'L ' + parseFloat(amount).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function verDetalleAgencias() {
        const modal = document.getElementById('modalAgencias');
        const list = document.getElementById('listaAgencias');
        list.innerHTML = '';

        if (window.agenciasDetalle) {
            window.agenciasDetalle.forEach(ag => {
                let badgeColor = 'gray';
                let icon = 'fa-minus-circle';

                if (ag.estado === 'Activa') { badgeColor = 'green'; icon = 'fa-check-circle'; }
                else if (ag.estado === 'Cerrada') { badgeColor = 'blue'; icon = 'fa-lock'; }

                list.innerHTML += `
                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-gray-800">${ag.nombre}</span>
                            <span class="text-xs font-bold text-${badgeColor}-600 uppercase flex items-center">
                                <i class="fas ${icon} mr-1"></i> ${ag.estado}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 flex justify-between">
                            <span>Apertura: <b>${ag.apertura}</b></span>
                            <span>Cierre: <b>${ag.cierre}</b></span>
                        </div>
                    </div>
                `;
            });
        }

        modal.classList.remove('hidden');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>