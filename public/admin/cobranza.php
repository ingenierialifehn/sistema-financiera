<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
$user = Auth::checkSession();
$userId = $user['id_usuario'];
$db = getDB();

// KPI Rapid Safe
$totalRecaudado = 0;
try {
    $hoy = date('Y-m-d');
    $totalRecaudado = $db->query("SELECT IFNULL(SUM(monto_pagado),0) FROM cuotas WHERE DATE(fecha_pago_real) = '$hoy'")->fetchColumn();
} catch (Exception $e) {
    $totalRecaudado = 0;
}

$pageTitle = "Gestión de Cobranza";
require_once __DIR__ . '/includes/layout.php';
?>

<div class="container mx-auto pb-20">
    <!-- Header -->
    <div
        class="mb-4 flex flex-wrap justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-hand-holding-usd mr-3 text-green-600"></i>Gestión de Cobros
            </h1>
            <p class="text-sm text-gray-500 mt-1">Recaudado hoy: <span id="total-recaudado-display"
                    class="font-bold text-green-600">L <?php echo number_format($totalRecaudado, 2); ?></span></p>
        </div>
        <div class="flex gap-2">
            <a href="../../app/api/fix_assignments.php"
                class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-100 text-sm font-bold transition border border-indigo-200">
                <i class="fas fa-users-cog mr-2"></i> Asignaciones
            </a>
        </div>
    </div>

    <!-- Filtros y Tabs -->
    <div
        class="flex flex-col md:flex-row gap-4 mb-4 justify-between items-end md:items-center bg-white p-4 rounded shadow-sm">
        <?php $isAsesor = (stripos($userRole ?? '', 'Asesor') !== false || stripos($userRole ?? '', 'Oficial') !== false); ?>
        <div class="flex gap-4 items-center w-full md:w-auto">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase">Fecha</label>
                <input type="date" id="filtroFecha" value="<?php echo date('Y-m-d'); ?>" <?php echo $isAsesor ? 'disabled' : ''; ?>
                    class="border-gray-300 rounded text-sm px-3 py-1 bg-gray-50 text-gray-700 focus:ring-2 focus:ring-indigo-200 <?php echo $isAsesor ? 'bg-gray-100 cursor-not-allowed opacity-75' : ''; ?>"
                    onchange="refreshCurrentView()">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase">Agencia</label>
                <select id="filtroAgencia"
                    class="border-gray-300 rounded text-sm px-3 py-1 bg-gray-50 text-gray-700 focus:ring-2 focus:ring-indigo-200 min-w-[200px]"
                    onchange="refreshCurrentView()">
                    <option value="">Todas las Agencias</option>
                    <?php
                    try {
                        $ags = $db->query("SELECT id, nombre_agencia FROM agencias")->fetchAll();
                        foreach ($ags as $a)
                            echo "<option value='{$a['id']}'>{$a['nombre_agencia']}</option>";
                    } catch (Exception $e) {
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex bg-gray-100 p-1 rounded-lg">
            <button onclick="switchTab('pendientes')" id="tab-pendientes"
                class="px-4 py-2 rounded-md text-sm font-bold shadow bg-white text-indigo-600 transition flex items-center">
                <i class="fas fa-list-ul mr-2"></i> Por Cobrar
            </button>
            <button onclick="switchTab('historial')" id="tab-historial"
                class="px-4 py-2 rounded-md text-sm font-bold text-gray-500 hover:text-indigo-600 transition flex items-center">
                <i class="fas fa-history mr-2"></i> Historial Pagos
            </button>
        </div>
    </div>

    <!-- Container Vistas -->
    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 min-h-[400px]">

        <!-- Vista 1: Pendientes -->
        <div id="view-pendientes" class="overflow-x-auto transition-opacity duration-300">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-indigo-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Préstamo</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Próxima
                            Cuota</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acción
                        </th>
                    </tr>
                </thead>
                <tbody id="tablaCobros" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500 italic">Inicializando...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Vista 2: Historial -->
        <div id="view-historial" class="hidden overflow-x-auto transition-opacity duration-300">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-green-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-800 uppercase tracking-wider">Fecha
                            Pago</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-800 uppercase tracking-wider">
                            Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-800 uppercase tracking-wider">
                            Concepto</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-green-800 uppercase tracking-wider">Monto
                            Pagado</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-green-800 uppercase tracking-wider">
                            Ticket</th>
                    </tr>
                </thead>
                <tbody id="tablaHistorial" class="bg-white divide-y divide-gray-200">
                    <!-- Dynamic -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL COBRO -->
<div id="modalCobro" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()">
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div
            class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200">

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10 mb-4 sm:mb-0">
                        <i class="fas fa-cash-register text-green-600 text-lg"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Cobrar</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            <p id="modal-info" class="mb-4 bg-gray-50 p-2 rounded">Cargando...</p>

                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="bg-indigo-50 p-2 rounded text-center">
                                    <p class="text-[10px] text-indigo-500 uppercase font-bold">Capital Pendiente</p>
                                    <p class="text-sm font-bold text-indigo-700" id="modal-saldo-capital">L 0.00</p>
                                </div>
                                <div class="bg-gray-100 p-2 rounded text-center">
                                    <p class="text-[10px] text-gray-500 uppercase font-bold">Balance Total</p>
                                    <p class="text-sm font-bold text-gray-700" id="modal-saldo-balance">L 0.00</p>
                                </div>
                            </div>

                            <form id="formCobro" onsubmit="event.preventDefault(); submitCobro();">
                                <input type="hidden" id="cobro_prestamo_id">
                                <input type="hidden" id="cobro_cuota_monto">
                                <input type="hidden" id="cobro_saldo_cuota">

                                <div class="mb-4 relative">
                                    <label class="block text-gray-700 text-xs font-bold mb-2 uppercase">Monto a
                                        Recibir</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-lg">L</span>
                                        </div>
                                        <input type="number" step="0.01" id="monto_recibido"
                                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-8 pr-12 sm:text-2xl border-gray-300 rounded-md font-bold text-green-700 placeholder-gray-300"
                                            placeholder="0.00" required>
                                    </div>
                                </div>

                                <div class="flex items-start mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100">
                                    <div class="flex items-center h-5">
                                        <input id="es_capital" type="checkbox"
                                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="es_capital" class="font-medium text-blue-900">Abono a
                                            Capital</label>
                                        <p class="text-blue-700 text-xs mt-1">Reduce la deuda total, no paga cuotas
                                            específicas.</p>
                                    </div>
                                </div>

                                <div id="modal-calc-info"
                                    class="text-xs text-center text-gray-400 italic bg-gray-50 p-2 rounded">
                                    Esperando monto...
                                </div>

                                <!-- Botones Avanzados -->
                                <div class="mt-4 pt-3 border-t border-gray-100 flex justify-center gap-2">
                                    <button type="button" onclick="iniciarRefinanciamiento()"
                                        class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded hover:bg-purple-200 font-bold transition">
                                        <i class="fas fa-sync-alt mr-1"></i> Refinanciar
                                    </button>
                                    <button type="button" onclick="iniciarReestructuracion()"
                                        class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded hover:bg-orange-200 font-bold transition">
                                        <i class="fas fa-tools mr-1"></i> Reestructurar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                <button type="button" onclick="submitCobro()"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Confirmar
                </button>
                <button type="button" onclick="closeModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // const BASE_URL = '<?php echo BASE_URL; ?>'; // Removed to avoid syntax error
    let currentTab = 'pendientes';
    // Globals para Refinanciamiento
    let currentPrestamoId = 0;
    let currentSaldoCapital = 0;
    let currentSaldoBalance = 0;
    let currentClienteId = 0;

    document.addEventListener('DOMContentLoaded', () => {
        switchTab('pendientes');
    });
    async function switchTab(tab) {
        currentTab = tab;
        document.getElementById('tab-pendientes').className = tab === 'pendientes' ? 'px-4 py-2 rounded-md text-sm font-bold shadow bg-white text-indigo-600 transition flex items-center' : 'px-4 py-2 rounded-md text-sm font-bold text-gray-500 hover:text-indigo-600 transition flex items-center';
        document.getElementById('tab-historial').className = tab === 'historial' ? 'px-4 py-2 rounded-md text-sm font-bold shadow bg-white text-indigo-600 transition flex items-center' : 'px-4 py-2 rounded-md text-sm font-bold text-gray-500 hover:text-indigo-600 transition flex items-center';
        document.getElementById('view-pendientes').classList.toggle('hidden', tab !== 'pendientes');
        document.getElementById('view-historial').classList.toggle('hidden', tab !== 'historial');
        refreshCurrentView();
    }

    async function refreshCurrentView() {
        const fecha = document.getElementById('filtroFecha').value;
        const agencia = document.getElementById('filtroAgencia').value;

        // Ensure BASE_URL is available (fallback safe access)
        const baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '<?php echo BASE_URL; ?>';

        if (currentTab === 'pendientes') {
            document.getElementById('tablaCobros').innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-500 italic">Cargando...</td></tr>';
            try {
                const url = new URL(`${baseUrl}/app/api/cobranza/list_grouped.php`);
                url.searchParams.append('fecha', fecha);
                if (agencia) url.searchParams.append('agencia_id', agencia);

                const res = await fetch(url);
                const data = await res.json();
                if (data.success) renderTable(data.data);
                else document.getElementById('tablaCobros').innerHTML = `<tr><td colspan="5" class="p-4 text-center text-red-500">Error: ${data.message}</td></tr>`;
            } catch (e) {
                console.error(e);
                document.getElementById('tablaCobros').innerHTML = `<tr><td colspan="5" class="p-4 text-center text-red-500">Error de conexión</td></tr>`;
            }
        } else {
            loadHistorial(fecha, agencia, baseUrl);
        }
    }

    async function loadHistorial(fecha, agencia, baseUrl) {
        const tbody = document.getElementById('tablaHistorial');
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-500 italic">Cargando historial...</td></tr>';
        try {
            // Try to use new 'pagos' table logic if historial_pagos.php is updated, otherwise old logic
            const url = new URL(`${baseUrl}/app/api/cobranza/historial_pagos.php`);
            url.searchParams.append('fecha', fecha);
            if (agencia) url.searchParams.append('agencia_id', agencia);

            const res = await fetch(url);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(h => {
                    // Adapter for different API response structures
                    const fechaShow = h.fecha_hora || h.fecha_pago || h.fecha;
                    const clienteShow = h.cliente || h.nombre_completo;
                    const montoShow = h.monto || h.total_pagado || h.monto_total;
                    const idShow = h.id;

                    html += `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="px-6 py-4 text-sm text-gray-700">${fechaShow}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">${clienteShow}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${h.concepto || 'Pago Recibido'}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-green-600">L ${parseFloat(montoShow).toFixed(2)}</td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="window.open('${baseUrl}/public/admin/print_docs.php?type=ticket_pago&id=${idShow}', '_blank')" class="text-indigo-600 hover:text-indigo-900" title="Reimprimir Ticket"><i class="fas fa-print"></i></button>
                            </td>
                        </tr>
                     `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-500 italic">No hay pagos registrados en esta fecha.</td></tr>';
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-red-500">Error al cargar historial</td></tr>`;
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('tablaCobros');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-12 text-center text-gray-400 flex flex-col items-center"><i class="fas fa-search text-3xl mb-2"></i><span>No se encontraron préstamos activos.</span></td></tr>';
            return;
        }

        let html = '';
        data.forEach(r => {
            const capital = parseFloat(r.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 });
            const montoCuota = r.monto_cuota ? parseFloat(r.monto_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 }) : '0.00';
            const saldoCap = r.saldo_capital || 0;
            const saldoBal = r.saldo_balance || 0;

            let accionBtn, proxCuotaInfo;

            if (r.tiene_refinanciamiento) {
                accionBtn = `<button onclick="Swal.fire('Atención', 'Este cliente tiene una solicitud de refinanciamiento en proceso. No se pueden realizar cobros hasta que se apruebe o rechace la solicitud.', 'warning')" 
                class="bg-gray-300 text-gray-600 font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center ml-auto cursor-not-allowed transition">
                <i class="fas fa-lock mr-2"></i> En Proceso
            </button>`;
            } else if (r.cuota_id) {
                proxCuotaInfo = `
                <div>
                   <span class="text-xs font-bold text-gray-500 uppercase">Cuota #${r.numero_cuota}</span>
                   <div class="text-indigo-700 font-bold text-lg">L ${montoCuota}</div>
                   <div class="text-xs text-gray-400">${r.fecha_fmt}</div>
                </div>
            `;
                accionBtn = `<button onclick="openModal(${r.prestamo_id}, '${r.nombre_completo.replace(/'/g, "\\'")}', ${r.monto_cuota}, '${r.fecha_fmt}', ${saldoCap}, ${saldoBal}, ${r.id_cliente})" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center ml-auto transition hover:scale-105">
                <i class="fas fa-hand-holding-usd mr-2"></i> Cobrar
            </button>`;
            } else {
                proxCuotaInfo = `<span class="text-gray-400 text-sm italic">Sin pendientes</span>`;
                accionBtn = `<button onclick="openModal(${r.prestamo_id}, '${r.nombre_completo.replace(/'/g, "\\'")}', 0, 'N/A', ${saldoCap}, ${saldoBal}, ${r.id_cliente})" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center ml-auto transition">
                <i class="fas fa-plus-circle mr-2"></i> Abono Cap.
            </button>`;
            }
            // ... (Render HTML Row is mostly same) ...
            html += `
        <tr class="hover:bg-gray-50 transition border-b border-gray-100 table-row-animate">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">${r.nombre_completo}</div>
                <div class="text-xs text-gray-500">${r.numero_documento || 'Sin DNI'}</div>
                <!-- Risk Badge -->
                ${getRiskBadge(r.categoria_riesgo, r.dias_mora_global)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 font-medium">L ${capital}</div>
                <div class="text-xs text-gray-500">Préstamo #${r.prestamo_id} | ${r.modalidad}</div>
                <div class="mt-1 w-24 bg-gray-200 rounded-full h-1">
                    <div class="bg-indigo-500 h-1 rounded-full" style="width: ${Math.min(100, (r.pagadas / r.total_cuotas) * 100)}%"></div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${proxCuotaInfo}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${r.class_visual}">
                    ${r.estado_visual}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                ${accionBtn}
            </td>
        </tr>`;
        });
        tbody.innerHTML = html;
    }

    // ... (skip renderHistorial) ...

    // Modal Logic
    const modal = document.getElementById('modalCobro');
    const inputMonto = document.getElementById('monto_recibido');
    const infoCalc = document.getElementById('modal-calc-info');

    function openModal(prestamoId, nombre, cuotaMonto, fechaVenc, saldoCap, saldoBal, clienteId) {
        document.getElementById('modal-title').innerText = `Cobrar a ${nombre}`;
        document.getElementById('cobro_prestamo_id').value = prestamoId;
        document.getElementById('cobro_cuota_monto').value = cuotaMonto;

        // Globals Update
        currentPrestamoId = prestamoId;
        currentSaldoCapital = parseFloat(saldoCap);
        currentSaldoBalance = parseFloat(saldoBal);
        currentClienteId = clienteId;

        // Saldo Display
        document.getElementById('modal-saldo-capital').innerText = 'L ' + currentSaldoCapital.toLocaleString('es-HN', { minimumFractionDigits: 2 });
        document.getElementById('modal-saldo-balance').innerText = 'L ' + currentSaldoBalance.toLocaleString('es-HN', { minimumFractionDigits: 2 });

        document.getElementById('cobro_saldo_cuota').value = cuotaMonto; // Simplified

        if (cuotaMonto > 0) {
            inputMonto.value = Math.ceil(cuotaMonto);
        } else {
            inputMonto.value = '';
        }

        // Trigger calculation update immediately
        setTimeout(updateCalc, 150);
        document.getElementById('es_capital').checked = false;

        if (cuotaMonto > 0) {
            document.getElementById('modal-info').innerHTML = `Próxima cuota: <strong>L ${parseFloat(cuotaMonto).toFixed(2)}</strong><br><span class="text-xs text-gray-400">Vence: ${fechaVenc}</span>`;
        } else {
            document.getElementById('modal-info').innerHTML = `Sin cuotas pendientes. Abono a capital disponible.`;
        }

        // ... rest (update class)
        infoCalc.innerText = "Ingrese monto...";
        infoCalc.className = "text-xs text-center text-gray-400 italic bg-gray-50 p-2 rounded";

        modal.classList.remove('hidden');
        setTimeout(() => inputMonto.focus(), 100);
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    inputMonto.addEventListener('keyup', updateCalc);
    document.getElementById('es_capital').addEventListener('change', updateCalc);

    function updateCalc() {
        const val = parseFloat(inputMonto.value) || 0;
        const isCap = document.getElementById('es_capital').checked;
        const cuota = parseFloat(document.getElementById('cobro_cuota_monto').value) || 0;

        if (val <= 0) {
            infoCalc.innerText = "Ingrese un monto mayor a 0.";
            infoCalc.className = "bg-gray-100 p-2 rounded text-red-500 font-bold text-center";
            return;
        }

        if (isCap) {
            infoCalc.innerHTML = `<span class="text-orange-600 font-bold">⚠️ Reducción Capital</span>: Nuevo saldo capital = Capital - L ${val.toFixed(2)}`;
            infoCalc.className = "bg-orange-50 p-2 rounded border border-orange-100 text-xs text-center";
        } else {
            if (cuota > 0) {
                if (val >= cuota) {
                    infoCalc.innerHTML = `<span class="text-green-600 font-bold">✅ Paga Cuota</span> (L ${cuota.toFixed(2)})<br>Saldo favor/sgte: L ${(val - cuota).toFixed(2)}`;
                    infoCalc.className = "bg-green-50 p-2 rounded border border-green-100 text-xs text-center";
                } else {
                    infoCalc.innerHTML = `<span class="text-yellow-600 font-bold">⚠️ Pago Parcial</span><br>Faltante para cuota: L ${(cuota - val).toFixed(2)}`;
                    infoCalc.className = "bg-yellow-50 p-2 rounded border border-yellow-100 text-xs text-center";
                }
            } else {
                infoCalc.innerText = "Abono general.";
            }
        }
    }

    async function submitCobro() {
        const prestamoId = document.getElementById('cobro_prestamo_id').value;
        const monto = document.getElementById('monto_recibido').value;
        const esCapital = document.getElementById('es_capital').checked;

        if (!monto || parseFloat(monto) <= 0) {
            Swal.fire('Atención', 'Ingrese un monto mayor a 0', 'warning');
            return;
        }

        try {
            const res = await fetch(`${BASE_URL}/app/api/cobranza/process_payment.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    prestamo_id: prestamoId,
                    monto: monto,
                    es_capital: esCapital
                })
            });
            const result = await res.json();

            if (result.success) {
                closeModal();
                // Actualizar KPI
                if (result.nuevo_total_hoy !== undefined) {
                    const kpi = document.getElementById('total-recaudado-display');
                    if (kpi) {
                        kpi.innerText = 'L ' + parseFloat(result.nuevo_total_hoy).toLocaleString('es-HN', { minimumFractionDigits: 2 });
                        kpi.classList.add('bg-green-100', 'px-2', 'py-1', 'rounded');
                        setTimeout(() => kpi.classList.remove('bg-green-100', 'px-2', 'py-1', 'rounded'), 1500);
                    }
                }

                // Generar Tickets Automáticos
                if (result.pagos_ids && result.pagos_ids.length > 0) {
                    result.pagos_ids.forEach(id => {
                        window.open(`${BASE_URL}/public/admin/print_docs.php?type=ticket_pago&id=${id}`, '_blank');
                    });
                }

                Swal.fire({
                    title: 'Cobro Registrado',
                    text: result.message,
                    icon: 'success',
                    timer: 2000
                });
                refreshCurrentView(); // Reload active tab
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }
</script>

<!-- Modal Refinanciamiento / Reestructuración -->
<div id="modalRefinanciamiento" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-refin-title"
    role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"
            onclick="closeRefinModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div
            class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">

            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-4 sm:px-6">
                <div class="flex justify-between items-center text-white">
                    <h3 class="text-lg leading-6 font-bold flex items-center" id="refin_header_title">
                        <i class="fas fa-sync-alt mr-2"></i> <span id="refin_tipo_op">Refinanciamiento</span>
                    </h3>
                    <button onclick="closeRefinModal()" class="text-purple-200 hover:text-white transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <form id="formRefinanciamiento" onsubmit="submitRefinanciamiento(event)">
                    <!-- Info Préstamo Actual -->
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-4 mb-6 rounded-r">
                        <p class="text-xs font-bold text-orange-800 uppercase mb-1">Préstamo Actual a Liquidar</p>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">Saldo Capital Pendiente:</span>
                            <span class="font-bold text-red-600 text-lg" id="refin_saldo_pendiente">L 0.00</span>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-2 italic">
                            * Este monto se considerará liquidado al aprobarse el nuevo crédito.
                        </p>
                    </div>

                    <!-- Datos Nuevo Credito -->
                    <h4 class="font-bold text-gray-800 mb-3 border-b pb-1">Condiciones del Nuevo Crédito</h4>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Monto Solicitado</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">L</span>
                            </div>
                            <input type="number" id="refin_monto" required min="1" step="0.01"
                                class="pl-8 focus:ring-purple-500 focus:border-purple-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2"
                                placeholder="0.00">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Plazo (Meses)</label>
                            <input type="number" id="refin_plazo" required min="1" step="1"
                                class="focus:ring-purple-500 focus:border-purple-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Frecuencia</label>
                            <select id="refin_modalidad" required
                                class="focus:ring-purple-500 focus:border-purple-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3">
                                <option value="Diario">Diario</option>
                                <option value="Semanal">Semanal</option>
                                <option value="Catorcenal">Catorcenal</option>
                                <option value="Mensual">Mensual</option>
                            </select>
                        </div>
                    </div>

                    <!-- Calculadora Mini -->
                    <div id="refin_calc_info" class="bg-gray-50 p-3 rounded text-sm text-gray-600 mb-4 hidden">
                        <div class="flex justify-between">
                            <span>Cuota Estimada:</span>
                            <span class="font-bold text-indigo-700" id="refin_cuota_est">L 0.00</span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span>Monto Neto a Recibir:</span>
                            <span class="font-bold text-green-600" id="refin_neto">Calculando...</span>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 flex flex-row-reverse gap-2">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:w-auto sm:text-sm">
                            <i class="fas fa-paper-plane mr-2 pt-1"></i> Solicitar
                        </button>
                        <button type="button" onclick="closeRefinModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Scripts de Refinanciamiento
    function iniciarRefinanciamiento() {
        setupRefinModal('Refinanciamiento');
    }

    function iniciarReestructuracion() {
        setupRefinModal('Reestructuración');
    }

    function setupRefinModal(tipo) {
        if (!currentPrestamoId) return;
        closeModal(); // Cerrar cobro

        document.getElementById('refin_tipo_op').innerText = tipo;
        document.getElementById('refin_saldo_pendiente').innerText = 'L ' + currentSaldoCapital.toLocaleString('es-HN', { minimumFractionDigits: 2 });
        document.getElementById('formRefinanciamiento').reset();
        document.getElementById('refin_calc_info').classList.add('hidden');

        document.getElementById('modalRefinanciamiento').classList.remove('hidden');
    }

    function closeRefinModal() {
        document.getElementById('modalRefinanciamiento').classList.add('hidden');
    }

    // Listeners for Calc
    ['refin_monto', 'refin_plazo', 'refin_modalidad'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateRefin);
    });

    function calculateRefin() {
        const monto = parseFloat(document.getElementById('refin_monto').value) || 0;
        const plazo = parseInt(document.getElementById('refin_plazo').value) || 0;
        const mod = document.getElementById('refin_modalidad').value;

        if (monto > 0 && plazo > 0) {
            // Logica simplificada 11% mensual
            const total = monto + (monto * 0.11 * plazo);
            let n = plazo;
            if (mod === 'Diario') n *= 20;
            else if (mod === 'Semanal') n *= 4;
            else if (mod === 'Catorcenal') n *= 2;
            else if (mod === 'Mensual') n *= 1;

            const cuota = total / n;
            const neto = Math.max(0, monto - currentSaldoCapital);

            document.getElementById('refin_cuota_est').innerText = 'L ' + cuota.toLocaleString('es-HN', { minimumFractionDigits: 2 });
            document.getElementById('refin_neto').innerText = 'L ' + neto.toLocaleString('es-HN', { minimumFractionDigits: 2 }) + ' (Aprox)';

            document.getElementById('refin_calc_info').classList.remove('hidden');
        }
    }

    async function submitRefinanciamiento(e) {
        e.preventDefault();
        if (!confirm('¿Confirmar solicitud? El préstamo actual seguirá activo hasta la aprobación.')) return;

        const monto = document.getElementById('refin_monto').value;
        const plazo = document.getElementById('refin_plazo').value;
        const mod = document.getElementById('refin_modalidad').value;
        const tipo = document.getElementById('refin_tipo_op').innerText;

        // Check cliente ID
        if (!currentClienteId) {
            Swal.fire('Error', 'No se ha identificado el cliente.', 'error');
            return;
        }

        const obs = `SOLICITUD DE ${tipo.toUpperCase()} del Préstamo #${currentPrestamoId}. Se debe liquidar Saldo Capital de L ${currentSaldoCapital.toFixed(2)}.`;

        const fd = new FormData();
        fd.append('cliente_id', currentClienteId);
        fd.append('monto', monto);
        fd.append('plazo_meses', plazo);
        fd.append('modalidad', mod);
        fd.append('observaciones', obs);
        fd.append('es_refinanciamiento', '1'); // Bypass active check

        try {
            const res = await fetch(`${BASE_URL}/app/api/prestamos/create.php`, {
                method: 'POST',
                body: fd
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire('Solicitud Enviada', 'Se ha creado la solicitud de ' + tipo, 'success');
                closeRefinModal();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }
    function getRiskBadge(categoria, dias) {
        categoria = categoria || 'A';
        dias = dias || 0;

        let color = 'green';
        let label = 'A';

        if (categoria === 'A') { color = 'green'; label = 'A'; }
        else if (categoria === 'B') { color = 'yellow'; label = 'B'; }
        else if (categoria === 'C') { color = 'orange'; label = 'C'; }
        else if (categoria === 'D') { color = 'red'; label = 'D'; }
        else { color = 'red'; label = 'E'; }

        if (dias === 0 && categoria === 'A') return `
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                A (0d)
            </span>
        `;

        return `
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-${color}-100 text-${color}-800">
                ${label} (${dias}d)
            </span>
        `;
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>