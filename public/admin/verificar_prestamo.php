<?php
/**
 * Verificar Préstamo
 * Vista de detalle para verificación de campo
 */

$prestamoId = $_GET['id'] ?? null;
if (!$prestamoId || !Auth::hasPermission('verificacion_campo.verify')) {
    echo "<script>window.location.href = '" . base_url('public/admin/verificacion_campo.php') . "';</script>";
    exit;
}

require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="<?php echo base_url('public/admin/verificacion_campo.php'); ?>"
                class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Verificación de Préstamo #<span
                        id="headerPrestamoId"></span></h2>
                <p class="text-gray-600">Revise y valide la información antes de autorizar.</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Columna Izquierda: Datos del Cliente y Negocio -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button onclick="switchTab('cliente')" id="tab-cliente"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                        Datos Cliente
                    </button>
                    <button onclick="switchTab('negocio')" id="tab-negocio"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Negocio
                    </button>
                    <button onclick="switchTab('prestamo')" id="tab-prestamo"
                        class="w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Préstamo
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Tab Cliente -->
                <div id="content-cliente">
                    <form id="formCliente">
                        <input type="hidden" id="clienteId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                                <input type="text" id="clienteNombre" name="nombre"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">DNI / Identidad</label>
                                <input type="text" id="clienteDni" name="dni"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                <input type="text" id="clienteTelefono" name="telefono"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                <textarea id="clienteDireccion" name="direccion" rows="3"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                            <!-- Agregar más campos según DB clientes si es necesario -->
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" onclick="saveCliente()"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                Guardar Cliente
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Negocio -->
                <div id="content-negocio" class="hidden">
                    <form id="formNegocio">
                        <input type="hidden" id="negocioId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Negocio</label>
                                <input type="text" id="negocioNombre" name="nombre_negocio"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo / Rubro</label>
                                <input type="text" id="negocioRubro" name="giro_negocio"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección del
                                    Negocio</label>
                                <textarea id="negocioDireccion" name="direccion_negocio" rows="3"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ingresos Aprox.</label>
                                <input type="number" step="0.01" id="negocioIngresos" name="ingresos_promedio"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" onclick="saveNegocio()"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                Guardar Negocio
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Préstamo -->
                <div id="content-prestamo" class="hidden">
                    <form id="formPrestamo">
                        <input type="hidden" id="prestamoIdForm" value="<?php echo $prestamoId; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto Solicitado</label>
                                <input type="number" step="0.01" id="prestamoMonto"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Plazo (meses)</label>
                                <input type="number" id="prestamoPlazo"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tasa Interés (%)</label>
                                <input type="number" step="0.01" id="prestamoTasa"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad</label>
                                <select id="prestamoModalidad"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="Diario">Diario</option>
                                    <option value="Semanal">Semanal</option>
                                    <option value="Catorcenal">Catorcenal</option>
                                    <option value="Mensual">Mensual</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Día de Pago</label>
                                <input type="number" id="prestamoDiaPago"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desembolso
                                    (Sug.)</label>
                                <input type="date" id="prestamoFecha"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" onclick="savePrestamo()"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                Guardar Préstamo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Acciones de Verificación -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Dictamen de Verificación</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comentarios de Verificación</label>
                <textarea id="comentarioVerificacion" rows="6"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingrese las observaciones del campo..."></textarea>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <button onclick="verificar('autorizar')"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded transition flex justify-center items-center">
                    <i class="fas fa-check-circle mr-2"></i> Autorizar / Verificado
                </button>
                <button onclick="verificar('rechazar')"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded transition flex justify-center items-center">
                    <i class="fas fa-times-circle mr-2"></i> Rechazar
                </button>
                <a href="<?php echo base_url('public/admin/verificacion_campo.php'); ?>"
                    class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded transition text-center">
                    Cancelar
                </a>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Al autorizar, el préstamo cambiará a estado <strong>'verificado'</strong>.
                        Si rechaza, pasará a <strong>'Rechazado'</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const PRESTAMO_ID = <?php echo $prestamoId; ?>;
    const API_BASE_URL = '<?php echo base_url("app/api"); ?>';
    const VIEWS_BASE_URL = '<?php echo base_url("public/admin"); ?>';
</script>
<script src="<?php echo base_url('public/admin/assets/js/verificar_prestamo.js'); ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>