<?php
/**
 * Verificar Préstamo
 * Vista de detalle para verificación de campo
 */

require_once __DIR__ . '/../auth_check.php';

$prestamoId = $_GET['id'] ?? null;
if (!$prestamoId || !Auth::hasPermission('verificacion_campo.verify')) {
    echo "<script>window.location.href = '" . base_url('public/admin/verificacion_campo.php') . "';</script>";
    exit;
}

require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <a href="<?php echo base_url('public/admin/verificacion_campo.php'); ?>"
                class="text-gray-600 hover:text-gray-900 transition p-2 rounded-full hover:bg-gray-100">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 leading-tight">Verificación <span
                        class="hidden sm:inline">de Préstamo</span> #<span id="headerPrestamoId"></span></h2>
                <div class="flex items-center text-sm text-gray-600 space-x-2 mt-1">
                    <span id="badgeEstado"
                        class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-wide">Cargando...</span>
                    <span>&bull;</span>
                    <span id="clienteNombreHeader">Cargando...</span>
                </div>
            </div>
        </div>
        <div class="flex space-x-2 w-full md:w-auto">
            <button onclick="verFichaCliente()"
                class="flex-1 md:flex-none border border-indigo-600 text-indigo-600 hover:bg-indigo-50 font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center shadow-sm">
                <i class="fas fa-user-circle mr-2"></i> Ver Ficha
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-24 lg:pb-0">
    <!-- Columna Izquierda: Datos del Cliente y Negocio -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-200 overflow-x-auto scrollbar-hide">
                <nav class="-mb-px flex min-w-full" aria-label="Tabs">
                    <button onclick="switchTab('cliente')" id="tab-cliente"
                        class="flex-1 py-4 px-4 text-center border-b-2 font-semibold text-sm border-indigo-500 text-indigo-600 whitespace-nowrap transition-colors">
                        <i class="fas fa-user mr-2"></i>Cliente
                    </button>
                    <button onclick="switchTab('negocio')" id="tab-negocio"
                        class="flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap transition-colors">
                        <i class="fas fa-store mr-2"></i>Negocio
                    </button>
                    <button onclick="switchTab('prestamo')" id="tab-prestamo"
                        class="flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap transition-colors">
                        <i class="fas fa-money-bill-wave mr-2"></i>Préstamo
                    </button>
                </nav>
            </div>

            <div class="p-4 md:p-6 bg-gray-50/50">
                <!-- Tab Cliente -->
                <div id="content-cliente">
                    <form id="formCliente" class="space-y-4">
                        <input type="hidden" id="clienteId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nombre
                                    Completo</label>
                                <input type="text" id="clienteNombre" name="nombre"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">DNI /
                                    Identidad</label>
                                <input type="text" id="clienteDni" name="dni"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Teléfono</label>
                                <div class="flex">
                                    <a id="btnCall" href="#"
                                        class="flex-shrink-0 inline-flex items-center px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 text-gray-500 rounded-l-md hover:bg-gray-100">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                    <input type="tel" id="clienteTelefono" name="telefono"
                                        class="flex-1 min-w-0 block w-full border-gray-300 rounded-none rounded-r-lg focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                </div>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <label
                                    class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Dirección
                                    Domiciliaria</label>
                                <textarea id="clienteDireccion" name="direccion" rows="3"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5"></textarea>
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <button type="button" onclick="saveCliente()"
                                class="bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 px-5 py-2.5 rounded-lg shadow-sm font-medium transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Actualizar Cliente
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Negocio -->
                <div id="content-negocio" class="hidden">
                    <form id="formNegocio" class="space-y-4">
                        <input type="hidden" id="negocioId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nombre
                                    del Negocio</label>
                                <input type="text" id="negocioNombre" name="nombre_negocio"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tipo /
                                    Rubro</label>
                                <input type="text" id="negocioRubro" name="giro_negocio"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Ingresos
                                    Aprox.</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">L</span>
                                    </div>
                                    <input type="number" step="0.01" id="negocioIngresos" name="ingresos_promedio"
                                        class="block w-full pl-8 border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                </div>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <label
                                    class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Dirección
                                    del Negocio</label>
                                <textarea id="negocioDireccion" name="direccion_negocio" rows="3"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5"></textarea>
                            </div>
                        </div>

                        <!-- Fotos del Negocio -->
                        <div class="mt-6">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 border-b pb-2">Fotos
                                del Negocio</h4>
                            <div id="galleryNegocio" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                <!-- Images will be injected here via JS -->
                                <p class="col-span-full text-sm text-gray-400 italic">No hay fotos registradas.</p>
                            </div>
                        </div>

                        <!-- Garantías -->
                        <div class="mt-6">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 border-b pb-2">
                                Garantías</h4>
                            <div id="listGarantias" class="space-y-3">
                                <!-- Guarantee items will be injected here via JS -->
                                <p class="text-sm text-gray-400 italic">No hay garantías registradas.</p>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="button" onclick="saveNegocio()"
                                class="bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 px-5 py-2.5 rounded-lg shadow-sm font-medium transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Actualizar Negocio
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Préstamo -->
                <div id="content-prestamo" class="hidden">
                    <form id="formPrestamo" class="space-y-4">
                        <input type="hidden" id="prestamoIdForm" value="<?php echo $prestamoId; ?>">

                        <!-- Panel Resumen Calculado -->
                        <div
                            class="bg-indigo-50 rounded-lg p-4 border border-indigo-100 grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <span class="block text-xs text-indigo-500 uppercase font-bold">Neto a Entregar</span>
                                <span class="text-xl font-bold text-green-600" id="displayNeto">L ...</span>
                            </div>
                            <div class="md:text-center">
                                <span class="block text-xs text-indigo-500 uppercase font-bold">Cuota Estimada</span>
                                <span class="text-xl font-bold text-indigo-700" id="displayCuota">L 0.00</span>
                            </div>
                            <div class="md:text-right">
                                <span class="block text-xs text-indigo-500 uppercase font-bold">Total Pagar</span>
                                <span class="text-xl font-bold text-indigo-700" id="displayTotal">L 0.00</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Monto
                                    Solicitado</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">L</span>
                                    </div>
                                    <input type="number" step="100" id="prestamoMonto" onchange="calcularProyeccion()"
                                        class="block w-full pl-8 border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2.5 text-lg font-semibold">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tipo
                                    Crédito</label>
                                <select id="prestamoTipo"
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                    <option value="Nuevo">Nuevo</option>
                                    <option value="Recurrente">Recurrente</option>
                                    <option value="Refinanciamiento">Refinanciamiento</option>
                                    <option value="Paralelo">Paralelo</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Plazo
                                        (meses)</label>
                                    <input type="number" id="prestamoPlazo" onchange="calcularProyeccion()"
                                        class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tasa
                                        %</label>
                                    <input type="number" step="0.01" id="prestamoTasa" onchange="calcularProyeccion()"
                                        class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Modalidad
                                    de Pago</label>
                                <select id="prestamoModalidad" onchange="calcularProyeccion()"
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                                    <option value="Diario">Diario (20 días/mes)</option>
                                    <option value="Semanal">Semanal (4/mes)</option>
                                    <option value="Catorcenal">Catorcenal (2/mes)</option>
                                    <option value="Mensual">Mensual</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Sug.
                                    Fecha Pago/Entrega</label>
                                <input type="date" id="prestamoFecha"
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <button type="button" onclick="savePrestamo()"
                                class="bg-indigo-600 text-white hover:bg-indigo-700 px-5 py-2.5 rounded-lg shadow-lg hover:shadow-xl font-medium transition flex items-center transform active:scale-95">
                                <i class="fas fa-save mr-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Acciones (Desktop: Sidebar, Mobile: Sticky Footer) -->
    <div class="lg:col-span-1">
        <!-- Desktop Container -->
        <div class="hidden lg:block space-y-6 sticky top-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Dictamen Final</h3>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Comentarios de Verificación</label>
                    <textarea id="comentarioVerificacionDesktop" rows="8"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-3"
                        placeholder="Describa el resultado de la visita..."></textarea>
                </div>

                <div class="space-y-3">
                    <button onclick="verificar('autorizar', 'desktop')"
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition hover:-translate-y-0.5 flex justify-center items-center">
                        <i class="fas fa-check-circle mr-2 text-xl"></i> AUTORIZAR
                    </button>
                    <button onclick="verificar('rechazar', 'desktop')"
                        class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-bold py-3.5 px-4 rounded-xl border border-red-200 transition hover:-translate-y-0.5 flex justify-center items-center">
                        <i class="fas fa-times-circle mr-2 text-xl"></i> RECHAZAR
                    </button>
                    <a href="<?php echo base_url('public/admin/verificacion_campo.php'); ?>"
                        class="block w-full text-center text-gray-500 hover:text-gray-700 font-medium py-2">
                        Cancelar y Volver
                    </a>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Recuerde verificar la dirección del negocio y la capacidad de pago real del cliente antes de
                            autorizar.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Footer Action Bar -->
        <div
            class="fixed bottom-0 left-0 right-0 lg:hidden bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50">
            <div class="mb-3">
                <textarea id="comentarioVerificacionMobile" rows="2"
                    class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm p-2"
                    placeholder="Escriba el comentario de verificación aquí..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="verificar('rechazar', 'mobile')"
                    class="bg-red-100 text-red-700 font-bold py-3 rounded-lg border border-red-200 flex justify-center items-center active:bg-red-200">
                    <i class="fas fa-times mr-2"></i> Rechazar
                </button>
                <button onclick="verificar('autorizar', 'mobile')"
                    class="bg-green-600 text-white font-bold py-3 rounded-lg shadow-md flex justify-center items-center active:bg-green-700">
                    <i class="fas fa-check mr-2"></i> Autorizar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const PRESTAMO_ID = <?php echo $prestamoId; ?>;
    const API_BASE_URL = '<?php echo base_url("app/api"); ?>';
    const VIEWS_BASE_URL = '<?php echo base_url("public/admin"); ?>';
</script>
<script src="<?php echo base_url('public/admin/assets/js/verificar_prestamo.js?v=' . time()); ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>