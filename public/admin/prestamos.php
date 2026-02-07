<?php
/**
 * Gestión de Préstamos - Admin
 */

$pageTitle = 'Gestión de Préstamos';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Préstamos</h2>
            <p class="text-gray-600">Gestiona los préstamos del sistema</p>
        </div>
        <button id="btnNuevoPrestamo"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Nuevo Préstamo</span>
        </button>
    </div>
</div>

<!-- Modal: Abono a capital -->
<div id="abonoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Abono a capital</h3>
            <button onclick="closeAbonoModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="abonoForm" class="p-6">
            <input type="hidden" id="abonoPrestamoId">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" id="abonoMonto" step="0.01" min="0.01" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                    <input type="date" id="abonoFecha" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="abonoObs" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeAbonoModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">Registrar</button>
            </div>
        </form>
    </div>

</div>

<!-- Modal: Refinanciar 50% -->
<div id="refiModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Refinanciar 50%</h3>
            <button onclick="closeRefiModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="refiForm" class="p-6">
            <input type="hidden" id="refiPrestamoId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">Se creará un nuevo préstamo por el 50% del saldo del préstamo
                        seleccionado y ese monto se abonará al préstamo original.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad *</label>
                    <select id="refiModalidad" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="mensual">Mensual</option>
                        <option value="semanal">Semanal</option>
                        <option value="catorcenal">Catorcenal</option>
                        <option value="diario">Diario (L-V)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tasa (%) *</label>
                    <input type="number" id="refiTasa" step="0.01" min="0" max="100" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periodo (meses) *</label>
                    <input type="number" id="refiPeriodo" min="1" max="120" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de Pago *</label>
                    <input type="number" id="refiDiaPago" min="1" max="28" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desembolso *</label>
                    <input type="date" id="refiFecha" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="refiObs" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeRefiModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">Refinanciar</button>
            </div>
        </form>
    </div>
</div>

<!-- Búsqueda y filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <input type="text" id="searchInput" placeholder="Número, cliente..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select id="filterEstado"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="activo">Activo</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
                <option value="en_mora">En Mora</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <select id="filterCliente"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="flex items-end">
            <button id="btnBuscar"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>
</div>

<!-- Tabla de préstamos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuotas
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="prestamosTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="pagination" class="bg-gray-50 px-4 py-3 border-t border-gray-200"></div>
</div>

<!-- Modal para crear/editar préstamo -->
<div id="prestamoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Nuevo Préstamo</h3>
            <button id="btnCerrarModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="prestamoForm" class="p-6">
            <input type="hidden" id="prestamoId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select id="clienteId" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar cliente</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Solicitud *</label>
                    <select id="tipoPrestamo" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="Nuevo">Nuevo Crédito</option>
                        <option value="Refinanciamiento">Refinanciamiento</option>
                        <option value="Readecuacion">Readecuación</option>
                        <option value="Represtamo">Représtamo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto Prestado *</label>
                    <input type="number" id="montoPrestado" step="0.01" min="1" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tasa de Interés (%) *</label>
                    <input type="number" id="tasaInteres" step="0.01" min="0" max="100" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad *</label>
                    <select id="modalidad" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="mensual">Mensual</option>
                        <option value="semanal">Semanal</option>
                        <option value="catorcenal">Catorcenal</option>
                        <option value="diario">Diario (L-V)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Se sugerirá una tasa según la modalidad, puedes ajustarla.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periodo (meses) *</label>
                    <input type="number" id="periodoMeses" min="1" max="120" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Desembolso *</label>
                    <input type="date" id="fechaDesembolso" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de Pago *</label>
                    <input type="number" id="diaPago" min="1" max="28" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observaciones" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Resumen calculado -->
                <div class="md:col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2">Resumen del Préstamo</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600">Monto Total:</span>
                            <span class="font-semibold text-blue-900" id="montoTotalPreview">S/ 0.00</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Monto por Cuota:</span>
                            <span class="font-semibold text-blue-900" id="montoCuotaPreview">S/ 0.00</span>
                        </div>

                        <!-- Campos para Refinanciamiento -->
                        <div class="refi-field hidden">
                            <span class="text-gray-600">Saldo Anterior:</span>
                            <span class="font-semibold text-red-700" id="saldoAnteriorPreview">S/ 0.00</span>
                        </div>
                        <div class="refi-field hidden">
                            <span class="text-gray-600 font-bold">Neto a Entregar:</span>
                            <span class="font-bold text-green-700" id="netoEntregarPreview">S/ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelar"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/prestamos.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>