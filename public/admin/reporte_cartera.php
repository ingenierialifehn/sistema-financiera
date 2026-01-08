<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/Auth.php';

$pageTitle = "Mi Cartera Global";
require_once __DIR__ . '/includes/layout.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📋 Mi Cartera de Clientes</h1>
            <p class="text-gray-500">Listado global de préstamos activos bajo su responsabilidad.</p>
        </div>
        <div class="flex gap-2">
            <a href="cobranza.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Cobranza
            </a>
            <button onclick="loadCartera()"
                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                <i class="fas fa-sync-alt mr-2"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 font-bold uppercase">Clientes Activos</p>
            <p class="text-2xl font-bold text-gray-800" id="stat-clientes">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
            <p class="text-sm text-gray-500 font-bold uppercase">Saldo x Cobrar</p>
            <p class="text-2xl font-bold text-gray-800" id="stat-saldo">L 0.00</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
            <p class="text-sm text-gray-500 font-bold uppercase">En Mora (>1 día)</p>
            <p class="text-2xl font-bold text-red-600" id="stat-mora">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 font-bold uppercase">Proyección Total</p>
            <p class="text-xl font-bold text-gray-800" id="stat-total">L 0.00</p>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Préstamo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progreso
                        Pagos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo
                        Pendiente</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="tablaCartera" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-500">Cargando cartera...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    document.addEventListener('DOMContentLoaded', loadCartera);

    async function loadCartera() {
        try {
            const res = await fetch(`${BASE_URL}/app/api/reportes/cartera_asesor.php`);
            const result = await res.json();

            if (result.success) {
                renderCartera(result.data);
                updateStats(result.data);
            } else {
                document.getElementById('tablaCartera').innerHTML = `<tr><td colspan="6" class="p-4 text-center text-red-500">Error: ${result.message}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            document.getElementById('tablaCartera').innerHTML = `<tr><td colspan="6" class="p-4 text-center text-red-500">Error de conexión</td></tr>`;
        }
    }

    function updateStats(data) {
        document.getElementById('stat-clientes').innerText = data.length;

        const saldo = data.reduce((acc, row) => acc + parseFloat(row.saldo_pendiente), 0);
        document.getElementById('stat-saldo').innerText = 'L ' + saldo.toLocaleString('es-HN', { minimumFractionDigits: 2 });

        const mora = data.filter(r => r.estado_cartera === 'Mora').length;
        document.getElementById('stat-mora').innerText = mora;

        const total = data.reduce((acc, row) => acc + parseFloat(row.total_a_pagar), 0);
        document.getElementById('stat-total').innerText = 'L ' + total.toLocaleString('es-HN', { minimumFractionDigits: 2 });
    }

    function renderCartera(data) {
        const tbody = document.getElementById('tablaCartera');

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-500">
            No tienes préstamos activos asignados.<br>
            <a href="../../app/api/fix_assignments.php" class="text-indigo-600 underline">Revisar Huérfanos</a>
        </td></tr>`;
            return;
        }

        let html = '';
        data.forEach(r => {
            const progreso = Math.round((r.cuotas_pagadas / r.total_cuotas) * 100) || 0;

            let statusBadge = `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold">Al Día</span>`;
            if (r.estado_cartera === 'Mora') {
                statusBadge = `<span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-bold">Mora (${r.dias_mora} días)</span>`;
            }

            // Manejo safe de proximo vencimiento
            let proxVenc = 'No aplica';
            if (r.proximo_vencimiento) {
                const [y, m, d] = r.proximo_vencimiento.split('-');
                proxVenc = `${d}/${m}/${y}`;
            } else if (r.total_cuotas == 0) {
                proxVenc = '<span class="text-red-500 font-bold">⚠️ Sin Plan</span>';
            }

            html += `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4">
                <div class="text-sm font-bold text-gray-900">${r.nombre_completo}</div>
                <div class="text-xs text-gray-500">DNI: ${r.dni || 'N/A'}</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm font-medium text-indigo-600">L ${parseFloat(r.monto_capital).toLocaleString('es-HN')}</div>
                <div class="text-xs text-gray-500">${r.modalidad} - ${r.plazo_meses} meses</div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${progreso}%"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-600">${r.cuotas_pagadas}/${r.total_cuotas}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1">Prox: ${proxVenc}</div>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="text-sm font-bold text-gray-900">L ${parseFloat(r.saldo_pendiente).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</div>
            </td>
            <td class="px-6 py-4 text-center">
                ${statusBadge}
            </td>
            <td class="px-6 py-4 text-right">
                <a href="cobranza.php" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Ir a Cobrar &rarr;</a>
            </td>
        </tr>
        `;
        });
        tbody.innerHTML = html;
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>