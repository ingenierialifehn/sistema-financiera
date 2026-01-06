<?php
/**
 * Detalle de Préstamo - Admin
 */

$pageTitle = 'Detalle de Préstamo';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="mb-6">
  <div class="flex justify-between items-center">
    <div>
      <h2 class="text-2xl font-bold text-gray-800">Detalle de Préstamo</h2>
      <p class="text-gray-600">Resumen y cronograma</p>
    </div>
    <a href="<?php echo $baseUrl; ?>/public/admin/prestamos.php" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg transition">
      <i class="fas fa-arrow-left mr-2"></i> Volver
    </a>
  </div>
</div>

<!-- Garantías del préstamo -->
<div class="bg-white rounded-lg shadow p-4 mt-6">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-lg font-semibold text-gray-800">Garantías</h3>
    <button id="btnNuevaGarantia" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md transition">
      <i class="fas fa-plus mr-1"></i> Nueva garantía
    </button>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
        </tr>
      </thead>
      <tbody id="garantiasTableBody" class="bg-white divide-y divide-gray-200">
        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
      </tbody>
    </table>
  </div>
  <div id="garantiasPagination" class="bg-gray-50 px-4 py-3 border-t border-gray-200"></div>
  
</div>

<!-- Modal: Garantía (crear/editar) -->
<div id="garantiaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
    <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
      <h3 class="text-xl font-bold text-gray-800" id="garantiaModalTitle">Nueva garantía</h3>
      <button onclick="closeGarantiaModal()" class="text-gray-400 hover:text-gray-600">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
    <form id="garantiaForm" class="p-6">
      <input type="hidden" id="garantiaId">
      <input type="hidden" id="garantiaPrestamoId">
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
          <input type="text" id="garantiaTipo" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
          <input type="number" id="garantiaMonto" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
          <textarea id="garantiaDescripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>
      </div>
      <div class="mt-6 flex justify-end space-x-3">
        <button type="button" onclick="closeGarantiaModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Resumen del préstamo -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
    <div>
      <div class="text-gray-500">Número</div>
      <div id="resNumero" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Cliente</div>
      <div id="resCliente" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Estado</div>
      <div id="resEstado" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Monto Prestado</div>
      <div id="resMontoPrestado" class="font-semibold">S/ 0.00</div>
    </div>
    <div>
      <div class="text-gray-500">Monto Total</div>
      <div id="resMontoTotal" class="font-semibold">S/ 0.00</div>
    </div>
    <div>
      <div class="text-gray-500">Saldo Pendiente</div>
      <div id="resSaldo" class="font-semibold text-red-600">S/ 0.00</div>
    </div>
    <div>
      <div class="text-gray-500">Tasa (%)</div>
      <div id="resTasa" class="font-semibold">0</div>
    </div>
    <div>
      <div class="text-gray-500">Modalidad</div>
      <div id="resModalidad" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Periodo (meses)</div>
      <div id="resPeriodo" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Día de Pago</div>
      <div id="resDiaPago" class="font-semibold">-</div>
    </div>
    <div>
      <div class="text-gray-500">Fecha Desembolso</div>
      <div id="resFechaDesembolso" class="font-semibold">-</div>
    </div>
  </div>

  <div class="mt-4 flex gap-3">
    <button id="btnAbono" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition">
      <i class="fas fa-plus-circle mr-1"></i> Abono a capital
    </button>
    <button id="btnRefi" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition">
      <i class="fas fa-exchange-alt mr-1"></i> Refinanciar 50%
    </button>
  </div>
</div>

<!-- Cronograma de cuotas -->
<div class="bg-white rounded-lg shadow overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Cuota</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagado</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
        </tr>
      </thead>
      <tbody id="cuotasTableBody" class="bg-white divide-y divide-gray-200">
        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
      </tbody>
    </table>
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
          <input type="number" id="abonoMonto" step="0.01" min="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
          <input type="date" id="abonoFecha" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <textarea id="abonoObs" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>
      </div>
      <div class="mt-6 flex justify-end space-x-3">
        <button type="button" onclick="closeAbonoModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">Registrar</button>
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
          <p class="text-sm text-gray-600">Se creará un nuevo préstamo por el 50% del saldo del préstamo seleccionado y ese monto se abonará al préstamo original.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad *</label>
          <select id="refiModalidad" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="mensual">Mensual</option>
            <option value="semanal">Semanal</option>
            <option value="catorcenal">Catorcenal</option>
            <option value="diario">Diario (L-V)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tasa (%) *</label>
          <input type="number" id="refiTasa" step="0.01" min="0" max="100" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Periodo (meses) *</label>
          <input type="number" id="refiPeriodo" min="1" max="120" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Día de Pago *</label>
          <input type="number" id="refiDiaPago" min="1" max="28" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desembolso *</label>
          <input type="date" id="refiFecha" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <textarea id="refiObs" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>
      </div>
      <div class="mt-6 flex justify-end space-x-3">
        <button type="button" onclick="closeRefiModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">Refinanciar</button>
      </div>
    </form>
  </div>
</div>

<script src="<?php echo $baseUrl; ?>/public/admin/assets/js/prestamo-detalle.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
