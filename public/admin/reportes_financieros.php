<?php
/**
 * Módulo de Reportes Financieros Avanzados
 */
require_once __DIR__ . '/../auth_check.php';
$pageTitle = 'Reportes Financieros';
$userAgenciaId = $_SESSION['id_agencia'] ?? $user['id_agencia'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> - Sistema Financiero
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const USER_AGENCIA_ID = <?php echo $userAgenciaId ? $userAgenciaId : 'null'; ?>;
    </script>
    <style>
        .reportes-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            /* Green/Emerald theme for Finance */
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-section h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .header-section p {
            margin: 0;
            opacity: 0.9;
        }

        .tabs-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .tab-button:hover {
            color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .tab-button.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #10b981;
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: end;
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .btn-primary {
            background: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }

        td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            color: #4b5563;
        }

        tr:hover td {
            background: #f9fafb;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-in {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .income-statement-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .income-statement-row.header {
            font-weight: bold;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .income-statement-row.total {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 2px solid #e5e7eb;
            border-bottom: none;
            margin-top: 0.5rem;
            padding-top: 1.5rem;
        }

        .sub-row {
            padding-left: 2rem;
            color: #6b7280;
            font-size: 0.9em;
        }

        @media print {

            .sidebar,
            .filter-section,
            .tabs-container {
                display: none;
            }

            .ml-64 {
                margin-left: 0;
                padding: 0;
            }

            .reportes-container {
                padding: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="ml-64 p-8 components-container">
        <div class="reportes-container">
            <div class="header-section">
                <div class="flex justify-between items-center">
                    <div>
                        <h1><i class="fas fa-file-invoice-dollar"></i> Reportes Financieros</h1>
                        <p>Auditoría, Estado de Resultados y Balance General</p>
                    </div>
                </div>
            </div>

            <div class="tabs-container">
                <button class="tab-button active" onclick="switchTab('audit')">
                    <i class="fas fa-search-dollar"></i> Auditoría de Movimientos
                </button>
                <button class="tab-button" onclick="switchTab('income')">
                    <i class="fas fa-balance-scale"></i> Estado de Resultados
                </button>
                <button class="tab-button" onclick="switchTab('balance')">
                    <i class="fas fa-landmark"></i> Balance Rápido
                </button>
            </div>

            <!-- SHARED FILTERS -->
            <div class="filter-section">
                <div class="form-group">
                    <label>Agencia</label>
                    <select id="filter-agency">
                        <option value="all">Todas las Agencias</option>
                        <!-- Agencies will be loaded via JS -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" id="filter-start" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="form-group">
                    <label>Fecha Fin</label>
                    <input type="date" id="filter-end" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button class="btn-primary" onclick="loadCurrentTab()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>

            <!-- TAB 1: AUDIT -->
            <div id="tab-audit" class="tab-content active">
                <div class="mb-4 flex gap-4">
                    <select id="filter-type" class="p-2 border rounded" onchange="loadAudit()">
                        <option value="all">Todos los Movimientos</option>
                        <option value="ingreso">Solo Ingresos</option>
                        <option value="egreso">Solo Egresos</option>
                    </select>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Agencia</th>
                            <th>Concepto</th>
                            <th>Categoría</th>
                            <th class="text-right text-green-600">Entrada</th>
                            <th class="text-right text-red-600">Salida</th>
                            <th class="text-right">Saldo Acumulado</th>
                        </tr>
                    </thead>
                    <tbody id="audit-table-body">
                        <!-- Content -->
                    </tbody>
                </table>
            </div>

            <!-- TAB 2: INCOME STATEMENT -->
            <div id="tab-income" class="tab-content">
                <div class="bg-white p-6 rounded-lg shadow-md max-w-4xl mx-auto">
                    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800 border-b pb-4">Estado de Resultados
                    </h2>

                    <div id="income-statement-content">
                        <!-- Loading... -->
                    </div>
                </div>
            </div>

            <!-- TAB 3: BALANCE -->
            <div id="tab-balance" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Activos -->
                    <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500">
                        <h3 class="text-xl font-bold text-gray-700 mb-4"><i class="fas fa-wallet mr-2"></i> ACTIVO</h3>
                        <div class="space-y-4" id="balance-assets">
                            <!-- Content -->
                        </div>
                    </div>

                    <!-- Pasivos -->
                    <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-red-500">
                        <h3 class="text-xl font-bold text-gray-700 mb-4"><i class="fas fa-hand-holding-usd mr-2"></i>
                            PASIVO</h3>
                        <div class="space-y-4" id="balance-liabilities">
                            <!-- Content -->
                        </div>
                    </div>

                    <!-- Patrimonio -->
                    <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-green-500">
                        <h3 class="text-xl font-bold text-gray-700 mb-4"><i class="fas fa-chart-line mr-2"></i>
                            PATRIMONIO</h3>
                        <div class="space-y-4" id="balance-equity">
                            <!-- Content -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        let currentTab = 'audit';

        async function init() {
            await loadAgencies();
            loadCurrentTab();
        }

        async function loadAgencies() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/reportes/financieros.php?action=get_agencies`);
                const data = await response.json();

                if (data.success && data.data) {
                    const select = document.getElementById('filter-agency');
                    // Keep the first option (all)
                    data.data.forEach(ag => {
                        const option = document.createElement('option');
                        option.value = ag.id;
                        option.textContent = ag.nombre;
                        select.appendChild(option);
                    });
                }
            } catch (e) {
                console.error("Error loading agencies", e);
            }
        }

        // Just add hardcoded options for now or fetch from consolidated report logic?
        // Let's try to fetch from an existing API if possible.
        // Actually, I can add a small endpoint to my new API to list agencies.

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');

            // Highlight button
            const buttons = document.querySelectorAll('.tab-button');
            if (tab === 'audit') buttons[0].classList.add('active');
            if (tab === 'income') buttons[1].classList.add('active');
            if (tab === 'balance') buttons[2].classList.add('active');

            currentTab = tab;
            loadCurrentTab();
        }

        function loadCurrentTab() {
            if (currentTab === 'audit') loadAudit();
            if (currentTab === 'income') loadIncomeStatement();
            if (currentTab === 'balance') loadBalance();
        }

        function getFilters() {
            return {
                start: document.getElementById('filter-start').value,
                end: document.getElementById('filter-end').value,
                agency: document.getElementById('filter-agency').value
            };
        }

        function formatMoney(amount) {
            return new Intl.NumberFormat('es-HN', { style: 'currency', currency: 'HNL' }).format(amount);
        }

        async function loadAudit() {
            const { start, end, agency } = getFilters();
            const type = document.getElementById('filter-type').value;

            const tbody = document.getElementById('audit-table-body');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4">Cargando...</td></tr>';

            try {
                const res = await fetch(`${BASE_URL}/app/api/reportes/financieros.php?action=audit&start_date=${start}&end_date=${end}&agency_id=${agency}&type=${type}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                tbody.innerHTML = data.data.length ? data.data.map(item => `
                    <tr>
                        <td class="whitespace-nowrap">${new Date(item.fecha).toLocaleDateString()}</td>
                        <td>${item.agencia}</td>
                        <td class="font-medium">${item.concepto}</td>
                        <td><span class="badge ${item.entrada > 0 ? 'badge-in' : 'badge-out'}">${item.categoria}</span></td>
                        <td class="text-right text-green-600 font-mono">${item.entrada > 0 ? formatMoney(item.entrada) : '-'}</td>
                        <td class="text-right text-red-600 font-mono">${item.salida > 0 ? formatMoney(item.salida) : '-'}</td>
                        <td class="text-right font-bold font-mono">${formatMoney(item.saldo)}</td>
                    </tr>
                `).join('') : '<tr><td colspan="7" class="text-center p-4">No hay movimientos en este periodo</td></tr>';

            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center p-4 text-red-500">Error: ${e.message}</td></tr>`;
            }
        }

        async function loadIncomeStatement() {
            const { start, end, agency } = getFilters();
            const container = document.getElementById('income-statement-content');
            container.innerHTML = '<div class="text-center p-8">Cargando...</div>';

            try {
                const res = await fetch(`${BASE_URL}/app/api/reportes/financieros.php?action=income_statement&start_date=${start}&end_date=${end}&agency_id=${agency}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                const r = data.data;

                container.innerHTML = `
                    <div class="income-statement-row header">
                        <span>INGRESOS OPERATIVOS</span>
                        <span class="text-green-600">${formatMoney(r.ingresos_operativos)}</span>
                    </div>
                    <div class="sub-row mb-4">Intereses y comisiones cobradas (11%)</div>

                    <div class="income-statement-row header text-red-600">
                        <span>(-) COSTOS OPERATIVOS</span>
                        <span>${formatMoney(r.costos_operativos)}</span>
                    </div>
                    <div class="sub-row mb-4">Comisiones asesores y gastos de campo</div>

                    <div class="income-statement-row total bg-gray-50 p-4 rounded">
                        <span>(=) UTILIDAD BRUTA</span>
                        <span class="${r.utilidad_bruta >= 0 ? 'text-green-700' : 'text-red-700'}">${formatMoney(r.utilidad_bruta)}</span>
                    </div>

                    <div class="mt-8 mb-2 font-bold text-gray-500 uppercase text-sm">Gastos Administrativos</div>
                    ${r.gastos_administrativos.detalles.map(d => `
                        <div class="income-statement-row py-2 text-sm">
                            <span class="pl-4 text-gray-600">${d.categoria}</span>
                            <span>${formatMoney(d.monto)}</span>
                        </div>
                    `).join('')}
                    
                    <div class="income-statement-row header text-red-600 mt-2">
                        <span>(-) TOTAL GASTOS ADMIN</span>
                        <span>${formatMoney(r.gastos_administrativos.total)}</span>
                    </div>

                    <div class="income-statement-row total bg-green-50 p-4 rounded mt-4 text-xl border-t-4 border-green-500">
                        <span>(=) UTILIDAD NETA</span>
                        <span class="${r.utilidad_neta >= 0 ? 'text-green-800' : 'text-red-800'}">${formatMoney(r.utilidad_neta)}</span>
                    </div>
                `;

            } catch (e) {
                container.innerHTML = `<div class="text-red-500 text-center">Error: ${e.message}</div>`;
            }
        }

        async function loadBalance() {
            const { agency } = getFilters();
            // Balance is usually snapshot, data range might not apply but keeping for consistnecy or ignored

            try {
                const res = await fetch(`${BASE_URL}/app/api/reportes/financieros.php?action=balance&agency_id=${agency}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                const b = data.data;

                document.getElementById('balance-assets').innerHTML = `
                   <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-600">Bancos</span>
                        <span class="font-bold">${formatMoney(b.activos.bancos)}</span>
                   </div>
                   <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-600">Cartera Vigente (Capital)</span>
                        <span class="font-bold">${formatMoney(b.activos.cartera)}</span>
                   </div>
                   <div class="flex justify-between items-center p-3 bg-green-50 rounded border-t border-green-200 mt-4">
                        <span class="font-bold text-green-800">TOTAL ACTIVOS</span>
                        <span class="font-bold text-green-800 text-lg">${formatMoney(b.activos.total)}</span>
                   </div>
                `;

                document.getElementById('balance-liabilities').innerHTML = `
                   <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-600">Cuentas por Pagar</span>
                        <span class="font-bold">${formatMoney(b.pasivos.cuentas_por_pagar)}</span>
                   </div>
                   <div class="flex justify-between items-center p-3 bg-red-50 rounded border-t border-red-200 mt-4">
                        <span class="font-bold text-red-800">TOTAL PASIVOS</span>
                        <span class="font-bold text-red-800 text-lg">${formatMoney(b.pasivos.total)}</span>
                   </div>
                `;

                document.getElementById('balance-equity').innerHTML = `
                   <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <span class="text-gray-600">Capital Inicial</span>
                        <span class="font-bold">${formatMoney(b.patrimonio)}</span>
                   </div>
                   <div class="flex justify-between items-center p-3 bg-green-100 rounded border-t border-green-300 mt-4">
                        <span class="font-bold text-green-900">TOTAL PATRIMONIO</span>
                        <span class="font-bold text-green-900 text-lg">${formatMoney(b.patrimonio)}</span>
                   </div>
                   <div class="text-xs text-gray-500 mt-2 text-center">
                        (Activo = Pasivo + Patrimonio + Utilidad)
                   </div>
                `;

            } catch (e) {
                console.error(e);
            }
        }

        // Fill agency dropdown (helper)


        init();
    </script>
</body>

</html>