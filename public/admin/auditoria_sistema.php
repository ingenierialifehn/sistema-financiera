<?php
require_once '../../app/core/Auth.php';
require_once '../../app/config/database.php';
Auth::requireAuth();
include 'includes/layout.php'; // Ajustar según estructura real
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-heartbeat text-red-600"></i> Monitor de Salud Financiera
    </h1>
    <p class="text-gray-600 mb-6">Auditoría automática de consistencia de datos (Saldo Guardado vs. Histórico).</p>

    <!-- Botón Actualizar -->
    <div class="flex justify-end mb-4">
        <button onclick="cargarAuditoria()"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            <i class="fas fa-sync-alt spin-on-click"></i> Ejecutar Análisis
        </button>
    </div>

    <!-- Grid de Paneles -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Panel Bancos -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border-t-4 border-blue-500">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-xl text-gray-800"><i class="fas fa-university mr-2"></i> Auditoría de Bancos
                </h2>
                <span id="badge-bancos" class="px-2 py-1 text-xs rounded-full bg-gray-200">Pendiente</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                                <th class="py-2 px-3 text-left">Banco</th>
                                <th class="py-2 px-3 text-right">Saldo Actual</th>
                                <th class="py-2 px-3 text-right">Cálculo Histórico</th>
                                <th class="py-2 px-3 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-bancos">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Panel Agencias -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border-t-4 border-green-500">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-xl text-gray-800"><i class="fas fa-store mr-2"></i> Auditoría de Cajas</h2>
                <span id="badge-agencias" class="px-2 py-1 text-xs rounded-full bg-gray-200">Pendiente</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs">
                                <th class="py-2 px-3 text-left">Agencia</th>
                                <th class="py-2 px-3 text-right">En Caja Operativa</th>
                                <th class="py-2 px-3 text-right">Cálculo Histórico</th>
                                <th class="py-2 px-3 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-agencias">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2 italic">* El cálculo histórico suma todos los ingresos y egresos
                    registrados en el sistema.</p>
            </div>
        </div>

        <!-- Panel Cartera (Ancho Completo) -->
        <div
            class="col-span-1 lg:col-span-2 bg-white rounded-lg shadow-lg overflow-hidden border-t-4 border-purple-500">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="font-bold text-xl text-gray-800"><i class="fas fa-wallet mr-2"></i> Consistencia de Cartera
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-purple-50 rounded border border-purple-100">
                    <p class="text-gray-500 text-sm uppercase">Total Capital Prestado</p>
                    <p class="text-2xl font-bold text-purple-700" id="total-prestado">L. 0.00</p>
                </div>
                <div class="p-4 bg-green-50 rounded border border-green-100">
                    <p class="text-gray-500 text-sm uppercase">Total Capital Recuperado</p>
                    <p class="text-2xl font-bold text-green-700" id="total-pagado">L. 0.00</p>
                </div>
                <div class="p-4 bg-blue-50 rounded border border-blue-100">
                    <p class="text-gray-500 text-sm uppercase">Saldo Cartera (Activo)</p>
                    <p class="text-2xl font-bold text-blue-700" id="saldo-cartera">L. 0.00</p>
                </div>
            </div>
        </div>

        <!-- Panel de Alertas Silenciosas (Bandeja de Entrada) -->
        <div
            class="col-span-1 lg:col-span-2 bg-white rounded-lg shadow-lg overflow-hidden border-t-4 border-yellow-500">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-xl text-gray-800"><i class="fas fa-envelope-open-text mr-2"></i> Bandeja de
                    Alertas (Auditoría)</h2>
                <span id="badge-alertas" class="px-2 py-1 text-xs rounded-full bg-gray-200">0 Nuevas</span>
            </div>
            <div class="p-6">
                <div id="lista-alertas" class="space-y-3">
                    <p class="text-center text-gray-500 italic">No hay alertas recientes.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', cargarAuditoria);

    function formatMoney(amount) {
        return new Intl.NumberFormat('es-HN', {
            style: 'currency',
            currency: 'HNL',
            minimumFractionDigits: 2
        }).format(amount);
    }

    function cargarAuditoria() {
        // Reset states
        const loadingRow = '<tr><td colspan="4" class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Analizando millones de registros...</td></tr>';
        document.getElementById('tabla-bancos').innerHTML = loadingRow;
        document.getElementById('tabla-agencias').innerHTML = loadingRow;

        fetch('../../app/api/reportes/auditoria_sistema.php')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    renderBancos(res.data.bancos);
                    renderAgencias(res.data.agencias);
                    renderCartera(res.data.cartera);
                    renderAlertas(res.data.alertas);
                } else {
                    alert('Error al cargar auditoría: ' + (res.message || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión con el servidor de auditoría.');
            });
    }

    function renderAlertas(data) {
        const container = document.getElementById('lista-alertas');
        const badge = document.getElementById('badge-alertas');
        
        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-500 italic">No hay alertas recientes.</p>';
            badge.textContent = '0 Nuevas';
            badge.className = 'px-2 py-1 text-xs rounded-full bg-gray-200';
            return;
        }

        let html = '';
        data.forEach(alert => {
            html += `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-yellow-600 mt-1"></i>
                    </div>
                    <div class="ml-3 w-full">
                        <p class="text-xs text-yellow-800 font-bold mb-1">${alert.fecha_generacion}</p>
                        <p class="text-sm text-yellow-700 font-medium">${alert.mensaje}</p>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        badge.textContent = `${data.length} Nuevas`;
        badge.className = 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-bold animate-pulse';
    }

    function renderBancos(data) {
        let html = '';
        let hasError = false;

        if (data.length === 0) {
            html = '<tr><td colspan="4" class="text-center py-4 text-gray-500">No hay bancos registrados.</td></tr>';
        } else {
            data.forEach(b => {
                const diff = parseFloat(b.diferencia);
                const isError = Math.abs(diff) > 0.01;
                if (isError) hasError = true;

                const colorClass = isError ? 'bg-red-50 text-red-700 font-bold' : 'text-gray-700';
                const diffColor = isError ? 'text-red-600' : 'text-green-600';
                const icon = isError ? '<i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>' : '<i class="fas fa-check-circle text-green-500 mr-2"></i>';

                html += `
                    <tr class="border-b hover:bg-gray-50 ${isError ? 'bg-red-50' : ''}">
                        <td class="py-3 px-3">${icon}${b.nombre}</td>
                        <td class="py-3 px-3 text-right">${formatMoney(b.saldo_actual)}</td>
                        <td class="py-3 px-3 text-right">${formatMoney(b.saldo_historico)}</td>
                        <td class="py-3 px-3 text-right font-bold ${diffColor}">${diff > 0 ? '+' : ''}${formatMoney(diff)}</td>
                    </tr>
                `;
            });
        }
        document.getElementById('tabla-bancos').innerHTML = html;

        const badge = document.getElementById('badge-bancos');
        badge.textContent = hasError ? 'DESCUADRE DETECTADO' : 'INTEGRIDAD OK';
        badge.className = hasError ? 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-bold' : 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-bold';
    }

    function renderAgencias(data) {
        let html = '';
        let hasError = false;

        if (data.length === 0) {
            html = '<tr><td colspan="4" class="text-center py-4 text-gray-500">No hay agencias registradas.</td></tr>';
        } else {
            data.forEach(a => {
                const diff = parseFloat(a.diferencia);
                const isError = Math.abs(diff) > 0.01;
                if (isError) hasError = true;

                const diffColor = isError ? 'text-red-600' : 'text-green-600';
                const icon = isError ? '<i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>' : '<i class="fas fa-check-circle text-green-500 mr-2"></i>';

                html += `
                    <tr class="border-b hover:bg-gray-50 ${isError ? 'bg-red-50' : ''}">
                        <td class="py-3 px-3">${icon}${a.nombre}</td>
                        <td class="py-3 px-3 text-right">${formatMoney(a.saldo_caja_operativa)}</td>
                        <td class="py-3 px-3 text-right">${formatMoney(a.saldo_historico_calculado)}</td>
                        <td class="py-3 px-3 text-right font-bold ${diffColor}">${diff > 0 ? '+' : ''}${formatMoney(diff)}</td>
                    </tr>
                `;
            });
        }
        document.getElementById('tabla-agencias').innerHTML = html;

        const badge = document.getElementById('badge-agencias');
        badge.textContent = hasError ? 'DESCUADRE DETECTADO' : 'INTEGRIDAD OK';
        badge.className = hasError ? 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-bold' : 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-bold';
    }

    function renderCartera(data) {
        document.getElementById('total-prestado').textContent = formatMoney(data.total_prestado || 0);
        document.getElementById('total-pagado').textContent = formatMoney(data.total_pagado || 0);
        document.getElementById('saldo-cartera').textContent = formatMoney(data.saldo_pendiente_real || 0);
    }
</script>

<?php include 'includes/footer.php'; ?>