<?php
/**
 * Gestión de Colaboradores - Admin
 */

$pageTitle = 'Gestión de Personal';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Personal</h2>
            <p class="text-gray-600">Administra colaboradores y sus accesos al sistema</p>
        </div>
        <button id="btnNuevoColaborador"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            <i class="fas fa-plus"></i>
            <span>Nuevo Colaborador</span>
        </button>
    </div>
</div>

<!-- Búsqueda y filtros -->
<div class="bg-white rounded-xl shadow-md p-5 mb-6 border border-gray-100">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Buscar</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="searchInput" placeholder="DNI, Nombre..."
                    class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 focus:bg-white transition-colors">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Agencia</label>
            <select id="filterAgencia"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 focus:bg-white">
                <option value="">Todas</option>
                <!-- Opciones dinámicas o estáticas -->
                <option value="Agencia Principal">Agencia Principal</option>
                <option value="Agencia Norte">Agencia Norte</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Puesto</label>
            <select id="filterPuesto"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-gray-50 focus:bg-white">
                <option value="">Todos</option>
                <option value="Gerente">Gerente</option>
                <option value="Cajero">Cajero</option>
                <option value="Asesor">Asesor</option>
                <option value="Seguridad">Seguridad</option>
            </select>
        </div>
        <div class="flex items-end">
            <button id="btnBuscar"
                class="w-full bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg transition shadow-md">
                Aplicar Filtros
            </button>
        </div>
    </div>
</div>

<!-- Tabla de colaboradores -->
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Colaborador</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">DNI /
                        Contacto</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Puesto
                        / Agencia</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Acciones</th>
                </tr>
            </thead>
            <tbody id="colaboradoresTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-indigo-500 mb-3"></i>
                            <span>Cargando colaboradores...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="pagination" class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between"></div>
</div>

<!-- Modal Maestro-Detalle -->
<div id="colaboradorModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] flex flex-col overflow-hidden transform transition-all scale-100">
        <!-- Header Modal -->
        <div
            class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-4 flex justify-between items-center shadow-md shrink-0">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-user-tie"></i>
                <span id="modalTitle">Nuevo Colaborador</span>
            </h3>
            <button id="btnCerrarModal"
                class="text-white hover:text-indigo-200 transition bg-white/10 hover:bg-white/20 rounded-full p-2 w-8 h-8 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body Scrollable -->
        <div class="overflow-y-auto p-6 bg-gray-50 flex-grow">
            <form id="colaboradorForm" class="space-y-6">
                <input type="hidden" id="colaboradorId">
                <input type="hidden" id="usuarioId"> <!-- ID del usuario si existe -->

                <!-- SECCIÓN A: Datos del Colaborador -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span
                            class="bg-indigo-100 text-indigo-600 w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">1</span>
                        Información Personal y Laboral
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">DNI *</label>
                            <input type="text" id="dni" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                            <input type="text" id="nombreCompleto" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" id="email" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sueldo Base *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L</span>
                                <input type="number" id="sueldoBase" step="0.01" required
                                    class="w-full pl-8 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado Laboral</label>
                            <select id="estadoLaboral"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="activo">Activo</option>
                                <option value="vacaciones">Vacaciones</option>
                                <option value="suspendido">Suspendido</option>
                                <option value="despido">Despido</option>
                                <option value="renuncia">Renuncia</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Agencia *</label>
                            <select id="agencia" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccione...</option>
                                <option value="Agencia Principal">Agencia Principal</option>
                                <option value="Agencia Norte">Agencia Norte</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Puesto *</label>
                            <select id="puesto" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">Seleccione...</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Cajero">Cajero</option>
                                <option value="Asesor">Asesor</option>
                                <option value="Seguridad">Seguridad</option>
                                <option value="IT">IT</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Toggle Acceso Sistema -->
                <div class="flex items-center">
                    <input type="checkbox" id="tieneAcceso"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition cursor-pointer">
                    <label for="tieneAcceso"
                        class="ml-2 block text-sm font-semibold text-gray-800 cursor-pointer select-none">
                        ¿Tiene acceso al sistema? (Crear Usuario Vinculado)
                    </label>
                </div>

                <!-- SECCIÓN B: Datos del Usuario -->
                <div id="seccionUsuario"
                    class="bg-indigo-50 p-5 rounded-xl shadow-inner border border-indigo-100 hidden transition-all duration-300">
                    <h4
                        class="text-lg font-semibold text-indigo-800 mb-4 flex items-center border-b border-indigo-200 pb-2">
                        <span
                            class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center mr-3 text-sm">2</span>
                        Credenciales de Acceso
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Nombre de Usuario *</label>
                            <input type="text" id="usuario"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Rol en Sistema *</label>
                            <select id="idRol"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                                <option value="">Seleccione Rol...</option>
                                <option value="1">Administrador</option>
                                <option value="2">Cajero</option>
                                <option value="3">Asesor</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Contraseña *</label>
                            <input type="password" id="password"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Confirmar Contraseña</label>
                            <input type="password" id="confirmPassword"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Jefe Directo
                                (Opcional)</label>
                            <select id="idJefeDirecto"
                                class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                                <option value="">Ninguno</option>
                                <!-- Se llenará dinámicamente con JS -->
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex items-center p-3 bg-yellow-50 text-yellow-800 rounded-lg text-sm">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                El saldo de la Caja Virtual se inicializará automáticamente en L 0.00.
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- Footer Modal -->
        <div class="bg-gray-100 px-6 py-4 flex justify-between items-center shrink-0 border-t">
            <div id="auditLog" class="text-xs text-gray-500 hidden">
                Creado por: <span id="logCreadoPor" class="font-medium">-</span> <br>
                Modificado: <span id="logModificado" class="font-medium">-</span>
            </div>
            <div class="flex space-x-3 ml-auto">
                <button type="button" id="btnCancelar"
                    class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition shadow-sm">
                    Cancelar
                </button>
                <div id="btnTraspasoContainer" class="hidden">
                    <button type="button"
                        class="px-5 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-medium transition shadow-sm flex items-center">
                        <i class="fas fa-exchange-alt mr-2"></i> Traspaso Rápido
                    </button>
                </div>
                <button type="submit" form="colaboradorForm"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition shadow-md flex items-center">
                    <i class="fas fa-save mr-2"></i> Guardar Registro
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/colaboradores.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>