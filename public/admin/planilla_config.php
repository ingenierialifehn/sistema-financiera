<?php
require_once __DIR__ . '/../auth_check.php';
$pageTitle = 'Configuración de Planilla';
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
</head>

<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-0 lg:ml-64 p-8 transition-all duration-300">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8"><i class="fas fa-sliders-h mr-2"></i> Configuración de
                Planilla</h1>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                <!-- General Configuration -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-700 border-b pb-2">Parámetros Generales</h2>
                    <form id="form-general" onsubmit="saveGeneral(event)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sueldo Base General (L.)</label>
                                <input type="number" step="0.01" id="config-sueldo-base"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mínimo Clientes Activos</label>
                                <input type="number" id="config-min-clientes"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mínimo Normalidad (%)</label>
                                <input type="number" step="0.01" id="config-min-normalidad"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border p-2"
                                    required>
                            </div>
                        </div>
                        <button type="submit"
                            class="mt-6 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 w-full transition">Guardar
                            Parámetros</button>
                    </form>
                </div>

                <!-- Mass Update Base Salary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-700 border-b pb-2">Actualización Masiva Sueldo
                        Base</h2>
                    <p class="text-sm text-gray-600 mb-4">Actualice el sueldo base para todos los asesores. Puede optar
                        por limpiar las excepciones individuales.</p>
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Nuevo Sueldo Base</label>
                            <input type="number" step="0.01" id="mass-salary"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                        </div>
                        <button onclick="confirmMassUpdate()"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 h-10 transition">Aplicar
                            Masivamente</button>
                    </div>
                </div>

                <!-- Tramos de Comisión -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1 xl:col-span-1">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-700 border-b pb-2">Tramos de Comisión por Saldo
                    </h2>
                    <div id="tramos-container" class="space-y-2 mb-4">
                        <!-- Items rendered by JS -->
                    </div>
                    <button onclick="addTramo()" class="text-sm text-indigo-600 font-medium hover:underline">+ Agregar
                        Tramo</button>
                </div>

                <!-- Escaladores de Normalidad -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1 xl:col-span-1">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-700 border-b pb-2">Escaladores de Normalidad</h2>
                    <div id="escaladores-container" class="space-y-2 mb-4">
                        <!-- Items rendered by JS -->
                    </div>
                    <button onclick="addEscalador()" class="text-sm text-indigo-600 font-medium hover:underline">+
                        Agregar Escalador</button>
                </div>

                <!-- Advisor Exceptions Table -->
                <div class="bg-white rounded-lg shadow-md p-6 col-span-1 xl:col-span-2">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-700 border-b pb-2">Excepciones por Asesor</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Agencia</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Asesor</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sueldo Base</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="advisors-table">
                                <!-- JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals -->
    <script>
        // State
        let currentConfig = {};
        let tramos = [];
        let escaladores = [];
        let advisors = [];

        // Fetch Data
        async function loadData() {
            try {
                const res = await fetch('../../app/api/planillas/config.php');
                const data = await res.json();

                if (data.success) {
                    currentConfig = data.data.config;
                    tramos = data.data.config.tramos_comision;
                    escaladores = data.data.config.escaladores_normalidad;
                    advisors = data.data.advisors;

                    renderGeneral();
                    renderTramos();
                    renderEscaladores();
                    renderAdvisors();
                } else {
                    alert('Error cargando configuración');
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderGeneral() {
            document.getElementById('config-sueldo-base').value = currentConfig.sueldo_base_general;
            document.getElementById('config-min-clientes').value = currentConfig.minimo_clientes;
            document.getElementById('config-min-normalidad').value = currentConfig.minimo_normalidad;
        }

        function renderTramos() {
            const container = document.getElementById('tramos-container');
            container.innerHTML = '';
            tramos.forEach((t, i) => {
                container.innerHTML += `
                    <div class="flex gap-2 items-center text-sm">
                        <span>De:</span>
                        <input type="number" value="${t.min}" onchange="updateTramo(${i}, 'min', this.value)" class="w-24 border rounded px-2 py-1">
                        <span>A:</span>
                        <input type="number" value="${t.max}" onchange="updateTramo(${i}, 'max', this.value)" class="w-24 border rounded px-2 py-1">
                        <span>Pago L.</span>
                        <input type="number" value="${t.monto}" onchange="updateTramo(${i}, 'monto', this.value)" class="w-20 border rounded px-2 py-1 font-bold">
                        <button onclick="removeTramo(${i})" class="text-red-500 hover:text-red-700 px-2">&times;</button>
                    </div>
                `;
            });
        }

        function renderEscaladores() {
            const container = document.getElementById('escaladores-container');
            container.innerHTML = '';
            escaladores.forEach((e, i) => {
                container.innerHTML += `
                    <div class="flex gap-2 items-center text-sm">
                        <span>Min %:</span>
                        <input type="number" value="${e.min}" step="0.01" onchange="updateEscalador(${i}, 'min', this.value)" class="w-20 border rounded px-2 py-1">
                        <span>Max %:</span>
                        <input type="number" value="${e.max}" step="0.01" onchange="updateEscalador(${i}, 'max', this.value)" class="w-20 border rounded px-2 py-1">
                        <span>Pago %:</span>
                        <input type="number" value="${e.porcentaje}" step="0.01" onchange="updateEscalador(${i}, 'porcentaje', this.value)" class="w-20 border rounded px-2 py-1 font-bold">
                        <button onclick="removeEscalador(${i})" class="text-red-500 hover:text-red-700 px-2">&times;</button>
                    </div>
                `;
            });
        }

        function renderAdvisors() {
            const tbody = document.getElementById('advisors-table');
            tbody.innerHTML = '';
            advisors.forEach(adv => {
                const isException = adv.sueldo_base_excepcion !== null;
                const salary = isException ? adv.sueldo_base_excepcion : currentConfig.sueldo_base_general;
                const badge = isException ? '<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Excepción</span>' : '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Estándar</span>';

                tbody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${adv.nombre_agencia || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${adv.nombre_completo}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            L. <input type="number" step="0.01" value="${salary}" 
                                onchange="updateAdvisorSalary(${adv.id_colaborador}, this.value, ${isException})"
                                class="border rounded px-2 py-1 w-24 ${isException ? 'border-yellow-400 bg-yellow-50' : ''}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${badge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            ${isException ? `<button onclick="updateAdvisorSalary(${adv.id_colaborador}, null, true)" class="text-indigo-600 hover:text-indigo-900">Restaurar</button>` : ''}
                        </td>
                    </tr>
                `;
            });
        }

        // Logic Helpers
        function updateTramo(idx, field, val) { tramos[idx][field] = Number(val); }
        function addTramo() { tramos.push({ min: 0, max: 0, monto: 0 }); renderTramos(); }
        function removeTramo(idx) { tramos.splice(idx, 1); renderTramos(); }

        function updateEscalador(idx, field, val) { escaladores[idx][field] = Number(val); }
        function addEscalador() { escaladores.push({ min: 0, max: 0, porcentaje: 0 }); renderEscaladores(); }
        function removeEscalador(idx) { escaladores.splice(idx, 1); renderEscaladores(); }

        async function saveGeneral(e) {
            e.preventDefault();
            const payload = {
                update_type: 'general',
                sueldo_base_general: document.getElementById('config-sueldo-base').value,
                minimo_clientes: document.getElementById('config-min-clientes').value,
                minimo_normalidad: document.getElementById('config-min-normalidad').value,
                tramos_comision: tramos,
                escaladores_normalidad: escaladores
            };

            const res = await fetch('../../app/api/planillas/config.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                alert('Guardado exitosamente');
                loadData();
            }
        }

        async function updateAdvisorSalary(id, val, isCurrentlyException) {
            // If val is null/empty, we might mean "reset to default"
            let confirmMsg = val === null
                ? "¿Desea eliminar la excepción y usar el sueldo base general?"
                : "¿Desea establecer una excepción de sueldo para este asesor?";

            if (!confirm(confirmMsg)) {
                loadData(); // Revert UI
                return;
            }

            const payload = {
                update_type: 'advisor_salary',
                id_colaborador: id,
                sueldo_base_excepcion: val
            };
            const res = await fetch('../../app/api/planillas/config.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (res.ok) loadData();
        }

        async function confirmMassUpdate() {
            const newVal = document.getElementById('mass-salary').value;
            if (!newVal) return;

            // Check for exceptions
            const exceptions = advisors.filter(a => a.sueldo_base_excepcion !== null);
            let clearExceptions = false;
            let clearIds = []; // empty if not clearing

            if (exceptions.length > 0) {
                if (confirm(`Hay ${exceptions.length} asesores con excepciones de sueldo. \n¿Desea nivelarlos a todos al nuevo sueldo base? \n(Cancelar para mantener sus excepciones)`)) {
                    clearExceptions = true;
                    clearIds = exceptions.map(a => a.id_colaborador);
                }
            }

            const payload = {
                update_type: 'bulk_salary',
                new_base_salary: newVal,
                clear_exceptions_ids: clearExceptions ? clearIds : []
            };

            const res = await fetch('../../app/api/planillas/config.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                alert('Actualización completa');
                loadData();
            }
        }

        loadData();
    </script>
</body>

</html>