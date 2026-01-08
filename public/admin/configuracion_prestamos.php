<?php
/**
 * Configuración de Préstamos
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/core/Helpers.php';

// Solo admin
AuthMiddleware::requireAdmin();

$pageTitle = 'Configuración de Préstamos';
require_once __DIR__ . '/includes/layout.php';

// Obtener valores actuales
$refinPorcentaje = getConfig('refinanciamiento_min_pagado_porcentaje', 50);
$tasaDefault = getConfig('tasa_interes_default', 5.00);
$moraPorDia = getConfig('mora_por_dia', 0.50);
$diasGracia = getConfig('dias_gracia', 3);
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Mantenimiento de Préstamos</h2>
            <p class="text-gray-600">Configuración global para créditos y refinanciamientos</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/public/admin/dashboard.php"
            class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg transition flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden max-w-4xl mx-auto">
    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-8 py-4">
        <h3 class="text-white font-bold text-lg flex items-center">
            <i class="fas fa-cogs mr-3"></i> Parámetros Globales
        </h3>
    </div>

    <form id="configForm" class="p-8 space-y-8">
        <!-- Refinanciamiento -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 flex items-center">
                <i class="fas fa-sync-alt text-purple-600 mr-2"></i> Refinanciamiento
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Porcentaje Mínimo Pagado (%)
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="1" min="0" max="100"
                            name="configs[refinanciamiento_min_pagado_porcentaje]"
                            value="<?php echo $refinPorcentaje; ?>" required
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        El cliente debe haber pagado este porcentaje del <strong>total del crédito</strong> para poder
                        refinanciar.
                    </p>
                </div>
            </div>
        </div>

        <!-- Excepciones Automáticas (Refinanciamientos, Représtamos, Readecuaciones) -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 flex items-center">
                <i class="fas fa-magic text-green-600 mr-2"></i> Aprobación Automática (Excepciones)
            </h4>
            <div class="bg-green-50 rounded-md p-4 mb-6 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Regla de Excepción</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Si el cliente tiene <strong>0 días de atraso</strong> y solicita un aumento entre el
                                <strong>0% y el X%</strong> del crédito actual, la solicitud pasará directamente a
                                <strong>"Listo para entrega"</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Habilitar Aprobación Automática
                    </label>
                    <select name="configs[refinanciamiento_auto_approve_enabled]"
                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                        <option value="1" <?php echo getConfig('refinanciamiento_auto_approve_enabled', 0) == 1 ? 'selected' : ''; ?>>Sí, Habilitar</option>
                        <option value="0" <?php echo getConfig('refinanciamiento_auto_approve_enabled', 0) == 0 ? 'selected' : ''; ?>>No, Deshabilitar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        % Máximo de Aumento Permitido
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="1" min="0" max="100"
                            name="configs[refinanciamiento_auto_approve_max_increase_percent]"
                            value="<?php echo getConfig('refinanciamiento_auto_approve_max_increase_percent', 25); ?>"
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribución de Intereses (Desglose) -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 flex items-center">
                <i class="fas fa-chart-pie text-blue-600 mr-2"></i> Distribución de Tasa Total
            </h4>
            <p class="text-sm text-gray-500 mb-4 bg-blue-50 p-2 rounded">
                La tasa total del préstamo se desglosa en tres componentes. Aquí puede definir los valores máximos
                permitidos y los valores por defecto.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Interés Puro -->
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <h5 class="font-bold text-gray-700 mb-3 text-center border-b pb-1">Interés Puro</h5>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Máximo (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_max_interes]"
                                value="<?php echo getConfig('tasa_max_interes', 4); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Default
                                (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_default_interes]"
                                value="<?php echo getConfig('tasa_default_interes', 4); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Gastos Financieros -->
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <h5 class="font-bold text-gray-700 mb-3 text-center border-b pb-1">Gastos Financieros</h5>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Máximo (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_max_gastos]"
                                value="<?php echo getConfig('tasa_max_gastos', 6); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Default
                                (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_default_gastos]"
                                value="<?php echo getConfig('tasa_default_gastos', 4); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Comisión -->
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <h5 class="font-bold text-gray-700 mb-3 text-center border-b pb-1">Comisión</h5>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Máximo (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_max_comision]"
                                value="<?php echo getConfig('tasa_max_comision', 6); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor Default
                                (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="configs[tasa_default_comision]"
                                value="<?php echo getConfig('tasa_default_comision', 3); ?>" required
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Condiciones Generales -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 flex items-center">
                <i class="fas fa-receipt text-green-600 mr-2"></i> Condiciones Generales
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tasa Interés Default (Total) se mantiene por compatibilidad visual, aunque se calculará dinamicamente idealmente -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tasa Total Referencial (%)
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="0.01" min="0" max="100" name="configs[tasa_interes_default]"
                            value="<?php echo $tasaDefault; ?>" required
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Este valor sirve como referencia rápida global, aunque el
                        desglose arriba tiene prioridad.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Días de Gracia
                    </label>
                    <input type="number" step="1" min="0" name="configs[dias_gracia]" value="<?php echo $diasGracia; ?>"
                        required
                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                    <p class="mt-2 text-sm text-gray-500">Días antes de aplicar mora.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mora Diaria (%)
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" step="0.01" min="0" max="100" name="configs[mora_por_dia]"
                            value="<?php echo $moraPorDia; ?>" required
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md py-3 pl-4">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">% daily</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-5 border-t border-gray-200 flex justify-end">
            <button type="submit"
                class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-3 px-6 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition transform hover:scale-105">
                <i class="fas fa-save mr-2"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    document.getElementById('configForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = { configs: {} };

        for (let [key, value] of formData.entries()) {
            if (key.startsWith('configs[')) {
                const configKey = key.replace('configs[', '').replace(']', '');
                data.configs[configKey] = value;
            }
        }

        try {
            Swal.fire({
                title: 'Guardando...',
                text: 'Actualizando parámetros del sistema',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const response = await fetch(`${BASE_URL}/app/api/configuracion/update.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire('Éxito', 'Configuración actualizada correctamente', 'success');
            } else {
                Swal.fire('Error', result.message || 'Error al guardar', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>