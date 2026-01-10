<?php
/**
 * Gestión de Gastos Operativos
 */
$pageTitle = 'Gastos Operativos';
require_once __DIR__ . '/../auth_check.php';
// requireViewPermission('gastos'); // Assuming we might add this permission later, but for now just check admin
if (!in_array($user['rol_nombre'], ['Administrador', 'Gerente', 'Supervisor'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Gastos Operativos</h2>
            <p class="text-gray-600">Registro centralizado de pagos y gastos por agencia</p>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-indigo-50 flex justify-between items-center">
            <h3 class="font-bold text-indigo-800 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar"></i> Registrar Nuevo Gasto
            </h3>
        </div>

        <form id="formGasto" class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Banco Selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria (Origen)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-university text-gray-400"></i>
                        </div>
                        <select id="selectBanco" name="banco_id" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Cargando bancos...</option>
                        </select>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">El monto se descontará de esta cuenta.</p>
                </div>

                <!-- Agencia Selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agencia (Destino del Gasto)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400"></i>
                        </div>
                        <select id="selectAgencia" name="agencia_id" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Cargando agencias...</option>
                        </select>
                    </div>
                </div>

                <!-- Categoria Selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría del Gasto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-tags text-gray-400"></i>
                        </div>
                        <select name="categoria" required
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccione Categoría...</option>
                            <option value="Planilla">Planilla</option>
                            <option value="Luz">Luz</option>
                            <option value="Agua">Agua</option>
                            <option value="Alquiler">Alquiler</option>
                            <option value="Internet">Internet</option>
                            <option value="Materiales">Materiales</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                </div>

                <!-- Monto Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto del Gasto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">L</span>
                        </div>
                        <input type="number" name="monto" min="0.01" step="0.01" required
                            class="block w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm"
                            placeholder="0.00">
                    </div>
                </div>
            </div>

            <!-- Descripcion Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción / Detalle</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 pt-2 pointer-events-none">
                        <i class="fas fa-align-left text-gray-400"></i>
                    </div>
                    <textarea name="descripcion" rows="3" required
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Detalle del pago (ej. Pago de alquiler mes de Enero)"></textarea>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-300 flex items-center gap-2 transform hover:-translate-y-0.5">
                    <i class="fas fa-save"></i> Registrar Gasto
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/gastos.js?v=<?php echo time(); ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>