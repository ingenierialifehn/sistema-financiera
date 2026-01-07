<?php
/**
 * Tesorería y Bancos - Admin
 */
$pageTitle = 'Tesorería y Bancos';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('tesoreria');
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tesorería y Bancos</h2>
            <p class="text-gray-600">Control de Saldos, Bancos y Patrimonio</p>
        </div>
        <div class="space-x-2">
            <?php if (tienePermiso('tesoreria', 'crear') || tienePermiso('tesoreria', 'editar')): ?>
                <button onclick="openModal('modalInyectar')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition shadow-md flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> <span>Inyectar Capital</span>
                </button>
            <?php endif; ?>
            <?php if (tienePermiso('tesoreria', 'crear')): ?>
                <button onclick="openModal('modalBanco')"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition shadow-md flex items-center gap-2">
                    <i class="fas fa-university"></i> <span>Nuevo Banco</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dashboard Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Saldo en Bancos</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="dashSaldoBancos">L 0.00</h3>
            </div>
            <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                <i class="fas fa-university text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500">
        <div class="flex justify-between items-start mb-2">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Efectivo en Sucursales</p>
            </div>
            <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                <i class="fas fa-cash-register text-lg"></i>
            </div>
        </div>
        <div class="space-y-1">
            <div class="flex justify-between items-end border-b border-gray-100 pb-1">
                <span class="text-sm text-gray-600">Cajas Operativas:</span>
                <span class="text-lg font-bold text-gray-800" id="dashSaldoCajas">L 0.00</span>
            </div>
            <div class="flex justify-between items-end pt-1">
                <span class="text-sm text-gray-600">Bóvedas:</span>
                <span class="text-lg font-bold text-emerald-700" id="dashSaldoBovedas">L 0.00</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-600">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Patrimonio Disponible</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="dashPatrimonio">L 0.00</h3>
            </div>
            <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                <i class="fas fa-chart-pie text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Cuentas Bancarias -->
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">Cuentas Bancarias</h3>
        <button onclick="loadTesoreriaData()" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            <i class="fas fa-sync-alt mr-1"></i> Actualizar
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banco
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.
                        Cuenta</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moneda
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="bancosTableBody" class="bg-white divide-y divide-gray-200">
                <!-- Loaded via JS -->
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Cargando cuentas...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Banco -->
<div id="modalBanco"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Nueva Cuenta Bancaria</h3>
            <button type="button" onclick="closeModal('modalBanco')" class="text-gray-400 hover:text-gray-600"><i
                    class="fas fa-times"></i></button>
        </div>
        <form id="formBanco" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre del Banco</label>
                <input type="text" name="nombre_banco" required
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Número de Cuenta</label>
                <input type="text" name="numero_cuenta" required
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="tipo_cuenta"
                        class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="Ahorro">Ahorro</option>
                        <option value="Corriente">Corriente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Moneda</label>
                    <select name="moneda"
                        class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="HNL">HNL</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Saldo Inicial</label>
                <input type="number" step="0.01" name="saldo_inicial" value="0.00"
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" onclick="closeModal('modalBanco')"
                    class="mr-3 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                <?php if (tienePermiso('tesoreria', 'crear')): ?>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Guardar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Modal Inyectar Capital -->
<div id="modalInyectar"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Inyectar Capital</h3>
            <button type="button" onclick="closeModal('modalInyectar')" class="text-gray-400 hover:text-gray-600"><i
                    class="fas fa-times"></i></button>
        </div>
        <form id="formInyectar" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cuenta Destino</label>
                <select id="selectBancoInyectar" name="banco_id" required
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <!-- Loaded via JS -->
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Monto a Inyectar</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span class="text-gray-500 sm:text-sm">L</span>
                    </div>
                    <input type="number" name="monto" min="0.01" step="0.01" required
                        class="block w-full px-3 py-2 border rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" onclick="closeModal('modalInyectar')"
                    class="mr-3 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                <?php if (tienePermiso('tesoreria', 'crear') || tienePermiso('tesoreria', 'editar')): ?>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">Inyectar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Modal Transferencia a Caja (Operatividad) -->
<div id="modalTransferencia"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden transform transition-all">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Transferir a Caja (Cajero)</h3>
            <button type="button" onclick="closeModal('modalTransferencia')"
                class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="formTransferencia" class="p-6 space-y-4">
            <input type="hidden" id="transferOrigenId" name="banco_id">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cuenta Origen</label>
                <input type="text" id="transferOrigenNombre" readonly
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Cajero Destino (Usuario)</label>
                <select id="selectCajero" name="usuario_destino_id" required
                    class="mt-1 block w-full px-3 py-2 border rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <!-- Loaded via JS -->
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Monto a Transferir</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span class="text-gray-500 sm:text-sm">L</span>
                    </div>
                    <input type="number" name="monto" min="0.01" step="0.01" required
                        class="block w-full px-3 py-2 border rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" onclick="closeModal('modalTransferencia')"
                    class="mr-3 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                <?php if (tienePermiso('tesoreria', 'crear') || tienePermiso('tesoreria', 'editar')): ?>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Transferir</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/tesoreria.js?v=<?php echo time(); ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>