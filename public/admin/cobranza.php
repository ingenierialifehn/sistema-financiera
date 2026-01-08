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
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let currentTab = 'pendientes';

    document.addEventListener('DOMContentLoaded', () => {
        switchTab('pendientes');
    });

    function refreshCurrentView() {
        if (currentTab === 'pendientes') loadTable();
        else loadHistorial();
    }

    function switchTab(tab) {
        currentTab = tab;
        // UI Buttons
        const btnPend = document.getElementById('tab-pendientes');
        const btnHist = document.getElementById('tab-historial');
        const viewPend = document.getElementById('view-pendientes');
        const viewHist = document.getElementById('view-historial');

        // Reset Classes
        const inactiveClass = "px-4 py-2 rounded-md text-sm font-bold text-gray-500 hover:text-indigo-600 transition flex items-center";
        const activeClassPend = "px-4 py-2 rounded-md text-sm font-bold shadow bg-white text-indigo-600 transition flex items-center";
        const activeClassHist = "px-4 py-2 rounded-md text-sm font-bold shadow bg-white text-green-600 transition flex items-center";

        if (tab === 'pendientes') {
            btnPend.className = activeClassPend;
            btnHist.className = inactiveClass;
            viewPend.classList.remove('hidden');
            viewHist.classList.add('hidden');
            loadTable();
        } else {
            btnHist.className = activeClassHist;
            btnPend.className = inactiveClass;
            viewPend.classList.add('hidden');
            viewHist.classList.remove('hidden');
            loadHistorial();
        }
    }

    async function loadTable() {
        const tbody = document.getElementById('tablaCobros');
        const fecha = document.getElementById('filtroFecha').value;
        const agencia = document.getElementById('filtroAgencia').value;

        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-400"><i class="fas fa-circle-notch fa-spin mr-2"></i>Cargando...</td></tr>';

        try {
            const res = await fetch(`${BASE_URL}/app/api/cobranza/list_grouped.php?fecha=${fecha}&agencia_id=${agencia}`);
            const result = await res.json();

            if (result.success) {
                renderTable(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-red-500">Error: ${result.message}</td></tr>`;
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-500">Error de conexión.</td></tr>';
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
            let accionBtn, proxCuotaInfo;

            if (r.cuota_id) {
                proxCuotaInfo = `
                <div>
                   <span class="text-xs font-bold text-gray-500 uppercase">Cuota #${r.numero_cuota}</span>
                   <div class="text-indigo-700 font-bold text-lg">L ${montoCuota}</div>
                   <div class="text-xs text-gray-400">${r.fecha_fmt}</div>
                </div>
            `;
                accionBtn = `<button onclick="openModal(${r.prestamo_id}, '${r.nombre_completo.replace(/'/g, "\\'")}', ${r.monto_cuota}, '${r.fecha_fmt}')" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center ml-auto transition hover:scale-105">
                <i class="fas fa-hand-holding-usd mr-2"></i> Cobrar
            </button>`;
            } else {
                proxCuotaInfo = `<span class="text-gray-400 text-sm italic">Sin pendientes</span>`;
                accionBtn = `<button onclick="openModal(${r.prestamo_id}, '${r.nombre_completo.replace(/'/g, "\\'")}', 0, 'N/A')" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center ml-auto transition">
                <i class="fas fa-plus-circle mr-2"></i> Abono Cap.
            </button>`;
            }

            html += `
        <tr class="hover:bg-gray-50 transition border-b border-gray-100 table-row-animate">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900">${r.nombre_completo}</div>
                <div class="text-xs text-gray-500">${r.numero_documento || 'Sin DNI'}</div>
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

    // Historial Functions
    async function loadHistorial() {
        const tbody = document.getElementById('tablaHistorial');
        const fecha = document.getElementById('filtroFecha').value;
        const agencia = document.getElementById('filtroAgencia').value;

        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-green-600"><i class="fas fa-circle-notch fa-spin mr-2"></i>Cargando pagos...</td></tr>';

        try {
            const res = await fetch(`${BASE_URL}/app/api/cobranza/historial_pagos.php?fecha=${fecha}&agencia_id=${agencia}`);
            const result = await res.json();

            if (result.success) {
                renderHistorial(result.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-500">Error cargando historial.</td></tr>';
            }
        } catch (e) { console.error(e); }
    }

    function renderHistorial(data) {
        const tbody = document.getElementById('tablaHistorial');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-12 text-center text-gray-400">No hay pagos registrados en esta fecha.</td></tr>';
            return;
        }

        let html = '';
        data.forEach(r => {
            let concepto = `Cuota #${r.numero_cuota}`;
            let badge = `<span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">Pagado</span>`;
            if (r.estado === 'parcial') {
                concepto += " (Parcial)";
                badge = `<span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">Parcial</span>`;
            }

            html += `
        <tr class="hover:bg-green-50 transition border-b border-gray-100">
            <td class="px-6 py-4 text-sm font-medium text-gray-700">${r.fecha_fmt}</td>
            <td class="px-6 py-4">
                <div class="text-sm font-bold text-gray-900">${r.nombre_completo}</div>
                <div class="text-xs text-gray-500">Préstamo #${r.prestamo_id}</div>
            </td>
             <td class="px-6 py-4">
                <div class="text-sm text-gray-700 font-medium">${concepto}</div>
                <div class="mt-1">${badge}</div>
            </td>
            <td class="px-6 py-4 text-right">
                <div class="text-sm font-bold text-green-700">L ${r.monto_fmt}</div>
            </td>
            <td class="px-6 py-4 text-center">
                 <a href="${BASE_URL}/public/admin/print_docs.php?type=ticket_pago&id=${r.cuota_id}" target="_blank" class="text-gray-400 hover:text-gray-700 transition">
                    <i class="fas fa-print fa-lg"></i>
                </a>
            </td>
        </tr>`;
        });
        tbody.innerHTML = html;
    }

    // Modal Logic
    const modal = document.getElementById('modalCobro');
    const inputMonto = document.getElementById('monto_recibido');
    const infoCalc = document.getElementById('modal-calc-info');

    function openModal(prestamoId, nombre, cuotaMonto, fechaVenc) {
        document.getElementById('modal-title').innerText = `Cobrar a ${nombre}`;
        document.getElementById('cobro_prestamo_id').value = prestamoId;
        document.getElementById('cobro_cuota_monto').value = cuotaMonto;

        document.getElementById('cobro_saldo_cuota').value = cuotaMonto; // Simplified

        inputMonto.value = '';
        document.getElementById('es_capital').checked = false;

        if (cuotaMonto > 0) {
            document.getElementById('modal-info').innerHTML = `Próxima cuota: <strong>L ${parseFloat(cuotaMonto).toFixed(2)}</strong><br><span class="text-xs text-gray-400">Vence: ${fechaVenc}</span>`;
        } else {
            document.getElementById('modal-info').innerHTML = `Sin cuotas pendientes. Abono a capital disponible.`;
        }

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

<?php include __DIR__ . '/includes/footer.php'; ?>