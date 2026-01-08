<?php
/**
 * Módulo de Apertura y Cierre de Caja
 */

$pageTitle = 'Control de Caja';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('caja');
require_once __DIR__ . '/includes/layout.php';

// Obtener usuario y agencia
$user = Auth::getCurrentUser();
if (session_status() === PHP_SESSION_NONE)
    session_start();
$idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];
$idUsuario = $user['id_usuario'];

if (!$idAgencia) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            Usuario no tiene agencia asignada
          </div>';
    exit;
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Control de Caja Diaria</h2>
    <p class="text-gray-600">Apertura y cierre de caja con conteo de billetaje</p>
</div>

<!-- Estado de Caja Actual -->
<div id="estadoCajaContainer" class="mb-6">
    <div class="flex items-center justify-center py-8">
        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
    </div>
</div>

<!-- Modal de Apertura de Caja -->
<div id="modalApertura" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-cash-register text-green-600"></i> Apertura de Caja
            </h3>
            <button id="btnCerrarModalApertura" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="formApertura" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Información del Sistema -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-900 mb-3">
                        <i class="fas fa-info-circle"></i> Información del Sistema
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saldo en Sistema:</span>
                            <span id="saldoSistemaApertura" class="font-bold text-blue-900">L. 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saldo en Bóveda:</span>
                            <span id="saldoBovedaApertura" class="font-bold text-indigo-900">L. 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Fecha:</span>
                            <span id="fechaApertura" class="font-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- Calculadora de Billetaje -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">
                        <i class="fas fa-calculator"></i> Conteo Físico de Efectivo
                    </h4>
                    <div id="calculadoraBilletajeApertura" class="space-y-2">
                        <!-- Se llenará dinámicamente -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-700">Total Contado:</span>
                            <span id="totalFisicoApertura" class="text-2xl font-bold text-green-600">L. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de Diferencia -->
            <div id="alertaDiferenciaApertura"
                class="hidden mt-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="mensajeDiferenciaApertura"></span>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones <span id="requiredObsApertura" class="text-red-500 hidden">*</span>
                </label>
                <textarea id="observacionesApertura" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Ingrese observaciones (requerido si hay diferencia)"></textarea>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarApertura"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                    <i class="fas fa-check"></i> Abrir Caja
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Cierre de Caja -->
<div id="modalCierre" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-door-closed text-red-600"></i> Cierre de Caja
            </h3>
            <button id="btnCerrarModalCierre" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="formCierre" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Información del Sistema -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-900 mb-3">
                        <i class="fas fa-info-circle"></i> Información del Sistema
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saldo Apertura:</span>
                            <span id="saldoAperturaInfo" class="font-semibold">L. 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saldo Actual Sistema (Caja):</span>
                            <span id="saldoSistemaCierre" class="font-bold text-blue-900">L. 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saldo en Bóveda:</span>
                            <span id="saldoBovedaCierre" class="font-bold text-indigo-900">L. 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hora Apertura:</span>
                            <span id="horaAperturaInfo" class="font-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- Calculadora de Billetaje -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">
                        <i class="fas fa-calculator"></i> Conteo Físico de Efectivo
                    </h4>
                    <div id="calculadoraBilletajeCierre" class="space-y-2">
                        <!-- Se llenará dinámicamente -->
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-700">Total Contado:</span>
                            <span id="totalFisicoCierre" class="text-2xl font-bold text-green-600">L. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de Diferencia -->
            <div id="alertaDiferenciaCierre"
                class="hidden mt-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="mensajeDiferenciaCierre"></span>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones <span id="requiredObsCierre" class="text-red-500 hidden">*</span>
                </label>
                <textarea id="observacionesCierre" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Ingrese observaciones (requerido si hay diferencia)"></textarea>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarCierre"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    <i class="fas fa-lock"></i> Cerrar Caja
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Jalar Fondos (Banco -> Bóveda) -->
<div id="jalarFondosModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Jalar Fondos desde Banco</h3>
            <button id="btnCerrarModalFondos" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="jalarFondosForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria de Origen *</label>
                    <select id="bancoIdFondos" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccione una cuenta...</option>
                    </select>

                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" id="montoFondos" step="0.01" min="0.01" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="0.00">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" id="referenciaFondos" maxlength="100"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Ej: Retiro #12345">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesFondos" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Detalles adicionales (opcional)"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarFondos"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition btn-save">
                    <i class="fas fa-download"></i> Jalar Fondos
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Retiro Bóveda a Caja (Bóveda -> Caja) -->
<div id="retiroBovedaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Retiro de Bóveda a Caja</h3>
            <button id="btnCerrarModalRetiro" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="retiroBovedaForm" class="p-6">
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-600">Disponible en Bóveda:</span>
                    <span class="font-bold text-indigo-700" id="modalSaldoBoveda">L. 0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Actual en Caja:</span>
                    <span class="font-bold text-green-700" id="modalSaldoCaja">L. 0.00</span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Retirar *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L.</span>
                        <input type="number" id="montoRetiro" step="0.01" min="0.01" required
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesRetiro" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Motivo del retiro..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarRetiro"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition btn-save">
                    <i class="fas fa-exchange-alt"></i> Procesar Retiro
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Devolución a Bóveda (Caja -> Bóveda) -->
<div id="devolucionBovedaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Devolver Efectivo a Bóveda</h3>
            <button id="btnCerrarModalDevolucion" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="devolucionBovedaForm" class="p-6">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-600">Actual en Caja:</span>
                    <span class="font-bold text-green-700" id="modalDevolucionSaldoCaja">L. 0.00</span>
                </div>
                <p class="text-xs text-blue-600 mt-2">
                    <i class="fas fa-info-circle"></i> Debe devolver todo el efectivo antes de cerrar caja.
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Devolver *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L.</span>
                        <input type="number" id="montoDevolucion" step="0.01" min="0.01" required
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="0.00">
                    </div>
                    <button type="button" id="btnDevolverTodo" class="text-xs text-blue-600 hover:underline mt-1">
                        Devolver Todo
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesDevolucion" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Motivo de la devolución..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarDevolucion"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition btn-save">
                    <i class="fas fa-undo"></i> Procesar Devolución
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Depósito a Banco (Caja -> Banco) -->
<div id="depositoBancoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-university text-blue-600"></i> Devolver Dinero a Banco
            </h3>
            <button id="btnCerrarModalDeposito" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="depositoBancoForm" class="p-6">
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Disponible en Caja:</span>
                    <span class="font-bold text-green-700" id="modalDepositoSaldoCaja">L. 0.00</span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banco Destino *</label>
                    <select id="bancoIdDeposito" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione una cuenta...</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Depositar *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L.</span>
                            <input type="number" id="montoDeposito" step="0.01" min="0.01" required
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Comprobante/Transferencia
                            *</label>
                        <input type="text" id="numeroDeposito" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Ej: DEP-123456">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observacionesDeposito" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Detalles adicionales..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="btnCancelarDeposito"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition btn-save">
                    <i class="fas fa-check"></i> Registrar Depósito
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cuadre Asesores -->
<div id="modalCuadreAsesores" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-user-shield text-indigo-600"></i> Cuadre de Asesores
            </h3>
            <button id="btnCerrarModalCuadre" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="cuadreAsesoresForm" class="p-6">
            <!-- Selección de Asesor -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Asesor *</label>
                <select id="asesorIdCuadre" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Cargando asesores...</option>
                </select>
                <!-- Info Recaudado Detallada -->
                <div id="infoRecaudadoContainer"
                    class="hidden mt-3 grid grid-cols-3 gap-2 bg-gray-50 p-2 rounded border border-gray-200 text-center">
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Recaudado</span>
                        <span id="recaudadoHoyDisplay" class="font-bold text-gray-800 text-lg">L. 0.00</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Entregado</span>
                        <span id="entregadoHoyDisplay" class="font-bold text-green-600 text-lg">L. 0.00</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Pendiente</span>
                        <span id="pendienteDisplay" class="font-bold text-red-600 text-lg">L. 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Área de Carga -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Columna Efectivo -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2 text-sm flex items-center"><i
                                class="fas fa-money-bill-wave mr-2 text-green-600"></i> Efectivo</h4>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L.</span>
                            <input type="number" id="montoEfectivoCuadre" step="0.01" min="0"
                                class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-green-500"
                                placeholder="0.00">
                        </div>
                        <button type="button" id="btnAgregarEfectivo"
                            class="mt-2 w-full bg-green-100 text-green-700 py-1 rounded text-xs font-bold hover:bg-green-200 border border-green-200">
                            <i class="fas fa-plus"></i> Agregar Efectivo
                        </button>
                    </div>

                    <!-- Columna Banco -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2 text-sm flex items-center"><i
                                class="fas fa-university mr-2 text-blue-600"></i> Banco</h4>
                        <div class="relative mb-2">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">L.</span>
                            <input type="number" id="montoBancoCuadre" step="0.01" min="0"
                                class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-blue-500"
                                placeholder="0.00">
                        </div>
                        <select id="bancoIdCuadre"
                            class="w-full text-xs px-2 py-1.5 border border-gray-300 rounded mb-2">
                            <option value="">Seleccione Banco...</option>
                        </select>
                        <input type="text" id="refBancoCuadre"
                            class="w-full text-xs px-2 py-1.5 border border-gray-300 rounded mb-2"
                            placeholder="Ref/Comprobante">

                        <button type="button" id="btnAgregarBanco"
                            class="w-full bg-blue-100 text-blue-700 py-1 rounded text-xs font-bold hover:bg-blue-200 border border-blue-200">
                            <i class="fas fa-plus"></i> Agregar Depósito
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de Items a Procesar -->
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">A Registrar</label>
                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Tipo</th>
                                <th class="px-3 py-2 text-left">Detalle</th>
                                <th class="px-3 py-2 text-right">Monto</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="listaItemsCuadre" class="divide-y divide-gray-100">
                            <!-- Items via JS -->
                            <tr id="emptyRow">
                                <td colspan="4" class="px-3 py-4 text-center text-gray-400 text-xs italic">Agregue
                                    montos arriba</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-indigo-50 font-bold text-indigo-900">
                            <tr>
                                <td colspan="2" class="px-3 py-2 text-right">Total:</td>
                                <td class="px-3 py-2 text-right" id="granTotalCuadre">L. 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" id="btnCancelarCuadre"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition btn-save shadow-lg">
                    <i class="fas fa-check-circle mr-2"></i> Registrar Todo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const USER_PERMISSIONS = {
        open_cash: <?php echo Auth::hasPermission('caja.open_cash') ? 'true' : 'false'; ?>,
        close_cash: <?php echo Auth::hasPermission('caja.close_cash') ? 'true' : 'false'; ?>,
        pull_funds_bank: <?php echo Auth::hasPermission('caja.pull_funds_bank') ? 'true' : 'false'; ?>,
        withdraw_vault: <?php echo Auth::hasPermission('caja.withdraw_vault') ? 'true' : 'false'; ?>,
        return_vault: <?php echo Auth::hasPermission('caja.return_vault') ? 'true' : 'false'; ?>,
        return_bank: <?php echo Auth::hasPermission('caja.return_bank') ? 'true' : 'false'; ?>
    };
</script>
<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/control_caja.js?v=<?php echo time(); ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>