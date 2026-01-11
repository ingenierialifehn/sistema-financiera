<?php
require_once __DIR__ . '/../auth_check.php';
$pageTitle = 'Generación de Planilla';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-0 lg:ml-64 p-8 transition-all duration-300">
        <div class="max-w-full mx-auto">

            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-file-invoice-dollar mr-2"></i>
                        Generación de Planilla</h1>
                    <p class="text-gray-600">Calcula y procesa el pago de comisiones y sueldos.</p>
                </div>
                <div class="bg-white p-3 rounded shadow flex gap-4">
                    <div>
                        <label class="block text-xs text-gray-500">Mes</label>
                        <select id="select-mes" class="font-bold border rounded p-1">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Año</label>
                        <input type="number" id="select-anio" class="font-bold border rounded p-1 w-20"
                            value="<?php echo date('Y'); ?>">
                    </div>
                    <button onclick="calculate()"
                        class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 transition font-medium">
                        <i class="fas fa-sync-alt mr-1"></i> Calcular
                    </button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loading" class="hidden text-center py-20">
                <i class="fas fa-spinner fa-spin text-4xl text-indigo-600"></i>
                <p class="mt-4 text-gray-600">Procesando cálculos...</p>
            </div>

            <!-- Content Area -->
            <div id="results-area" class="hidden">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-600">
                        <h3 class="text-xs font-bold text-gray-500 uppercase">Total a Pagar</h3>
                        <p class="text-2xl font-bold text-gray-800" id="stat-total">L. 0.00</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                        <h3 class="text-xs font-bold text-gray-500 uppercase">Sueldos Base</h3>
                        <p class="text-2xl font-bold text-gray-800" id="stat-base">L. 0.00</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                        <h3 class="text-xs font-bold text-gray-500 uppercase">Comisiones</h3>
                        <p class="text-2xl font-bold text-gray-800" id="stat-comision">L. 0.00</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-400">
                        <h3 class="text-xs font-bold text-gray-500 uppercase">Asesores</h3>
                        <p class="text-2xl font-bold text-gray-800" id="stat-count">0</p>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-20 bg-opacity-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asesor
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Métricas
                                        Cartera</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                        Candados</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sueldo
                                        Base</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        Comisión</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gastos
                                        Campo</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="table-body">
                                <!-- JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Floating Footer Confirmation -->
                <div
                    class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t p-4 shadow-lg z-50 flex justify-between items-center bg-opacity-100">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 font-bold mb-1">Banco de Salida</label>
                            <select id="select-banco" class="border rounded p-2 text-sm w-64 bg-gray-50" required>
                                <option value="">Seleccione Banco...</option>
                                <!-- Rendered by JS -->
                            </select>
                        </div>
                        <div class="text-right pl-6 border-l">
                            <span class="block text-xs text-gray-500">Total Definitivo</span>
                            <span class="text-xl font-bold text-indigo-700" id="footer-total">L. 0.00</span>
                        </div>
                    </div>
                    <button onclick="confirmarPlanilla()"
                        class="bg-green-600 text-white px-8 py-3 rounded-lg font-bold shadow hover:bg-green-700 transform hover:-translate-y-1 transition flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> CONFIRMAR PLANILLA
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Init
        document.getElementById('select-mes').value = new Date().getMonth() + 1;
        let pData = [];
        let bancos = [];

        async function loadBancos() {
            try {
                // Assuming existing endpoint for banks list
                const res = await fetch('../../app/api/tesoreria/get_bancos.php');
                const data = await res.json();
                if (data.success) {
                    bancos = data.data;
                    const select = document.getElementById('select-banco');
                    bancos.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.nombre_banco} - ${b.numero_cuenta} (L. ${Number(b.saldo_actual).toLocaleString()})`;
                        select.appendChild(opt);
                    });
                }
            } catch (e) { console.error('Error loading banks', e); }
        }

        async function calculate() {
            const loading = document.getElementById('loading');
            const results = document.getElementById('results-area');

            loading.classList.remove('hidden');
            results.classList.add('hidden');

            try {
                const res = await fetch('../../app/api/planillas/calcular.php');
                const data = await res.json();

                if (data.success) {
                    pData = data.data.preview;
                    renderTable();
                    loading.classList.add('hidden');
                    results.classList.remove('hidden');
                } else {
                    alert('Error: ' + data.message);
                    loading.classList.add('hidden');
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión');
                loading.classList.add('hidden');
            }
        }

        function renderTable() {
            const tbody = document.getElementById('table-body');
            tbody.innerHTML = '';

            let tBase = 0, tComm = 0, tTotal = 0;

            pData.forEach((row, idx) => {
                // Calculate Totals dynamically based on input values (for gastos_campo)
                const gastos = parseFloat(row.gastos_campo || 0);
                const totalRow = parseFloat(row.total_pagar);
                // Note: total_pagar from backend includes default gastos (0).

                tBase += parseFloat(row.sueldo_base);
                tComm += parseFloat(row.comision_final);
                tTotal += totalRow;

                // Badges
                const locked = row.candados_activados && row.candados_activados.length > 0;
                const lockHtml = locked
                    ? `<span class="text-red-500" title="${row.candados_activados.join('\n')}"><i class="fas fa-lock"></i> Bloqueado</span>`
                    : '<span class="text-green-500"><i class="fas fa-check"></i> OK</span>';

                tbody.innerHTML += `
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${row.nombre_completo}</div>
                            <div class="text-xs text-gray-500">${row.nombre_agencia}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs space-y-1">
                                <div class="flex justify-between"><span>Cartera:</span> <span class="font-bold">L. ${Number(row.metrics.saldo_cartera).toLocaleString()}</span></div>
                                <div class="flex justify-between"><span>Clientes:</span> <span class="font-bold">${row.metrics.clientes_activos}</span></div>
                                <div class="flex justify-between"><span>Normalidad:</span> <span class="font-bold ${row.metrics.normalidad_porcentaje < 92 ? 'text-red-600' : 'text-green-600'}">${row.metrics.normalidad_porcentaje}%</span></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-sm">${lockHtml}</td>
                        <td class="px-4 py-3 text-right text-sm">L. ${Number(row.sueldo_base).toLocaleString()}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <div class="font-bold">L. ${Number(row.comision_final).toLocaleString()}</div>
                            ${row.tramo_aplicado ? `<div class="text-xs text-gray-400">Tramo: L.${row.tramo_aplicado.monto}</div>` : ''}
                            ${row.escalador_aplicado ? `<div class="text-xs text-gray-400">Escalador: ${row.escalador_aplicado.porcentaje}%</div>` : ''}
                        </td>
                        <td class="px-4 py-3 text-right">
                             <input type="number" step="0.01" value="${gastos}" 
                                onchange="updateGastos(${idx}, this.value)"
                                class="w-24 text-right border rounded px-2 py-1 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-indigo-700 bg-gray-50 text-sm">
                            L. ${Number(totalRow).toLocaleString()}
                        </td>
                    </tr>
                `;
            });

            // Update Header Stats
            document.getElementById('stat-base').textContent = `L. ${tBase.toLocaleString()}`;
            document.getElementById('stat-comision').textContent = `L. ${tComm.toLocaleString()}`;
            document.getElementById('stat-total').textContent = `L. ${tTotal.toLocaleString()}`;
            document.getElementById('footer-total').textContent = `L. ${tTotal.toLocaleString()}`;
            document.getElementById('stat-count').textContent = pData.length;
        }

        function updateGastos(idx, val) {
            const newVal = parseFloat(val) || 0;
            const diff = newVal - (pData[idx].gastos_campo || 0);
            pData[idx].gastos_campo = newVal;

            // Recalculate row total in data
            pData[idx].total_pagar = parseFloat(pData[idx].sueldo_base) + parseFloat(pData[idx].comision_final) + newVal;

            renderTable();
        }

        async function confirmarPlanilla() {
            const bancoId = document.getElementById('select-banco').value;
            if (!bancoId) return Swal.fire('Error', 'Seleccione un banco de salida', 'error');

            const result = await Swal.fire({
                title: '¿Confirmar Pago de Planilla?',
                text: "Esta acción registrará el pago, descontará del banco y generará los gastos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Sí, Pagar'
            });

            if (result.isConfirmed) {
                // Prepare Payload
                const payload = {
                    banco_id: bancoId,
                    mes: document.getElementById('select-mes').value,
                    anio: document.getElementById('select-anio').value,
                    items: pData.map(p => ({
                        id_colaborador: p.id_colaborador,
                        gastos_campo: p.gastos_campo
                    }))
                };

                // Send
                try {
                    Swal.showLoading();
                    const res = await fetch('../../app/api/planillas/confirmar.php', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();

                    if (data.success) {
                        Swal.fire('¡Éxito!', 'Planilla procesada correctamente.', 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            }
        }

        loadBancos();
    </script>
</body>

</html>