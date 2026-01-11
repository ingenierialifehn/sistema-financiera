<?php
require_once __DIR__ . '/../../app/core/Auth.php';
require_once __DIR__ . '/../../app/config/database.php';
Auth::requireAuth();
include 'includes/layout.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">
        <i class="fas fa-history text-indigo-600"></i> Módulo de Reversiones y Corecciones
    </h1>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- Pestañas -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="#"
                    class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Revertir Pagos/Cuotas
                </a>
                <a href="#"
                    class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Anular Desembolsos
                </a>
                <a href="#"
                    class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Corrección de Gastos
                </a>
            </nav>
        </div>

        <!-- Buscador -->
        <div class="max-w-xl mx-auto mb-8">
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Transacción a Revertir</label>
            <div class="flex gap-2">
                <input type="text" id="busquedaTransaccion"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border"
                    placeholder="Buscar por ID Préstamo, Nombre Cliente o Referencia...">
                <button type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                <i class="fas fa-info-circle"></i> Ingrese el ID del préstamo para ver su historial de pagos reciente.
            </p>
        </div>

        <!-- Resultados (Tabla Placeholder) -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Transacción</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado Actual</th>
                        <th scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tablaResultados">
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <i class="fas fa-search text-gray-300 text-4xl mb-3 block"></i>
                            Realice una búsqueda para ver las transacciones revertibles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Lógica futura para reversiones
    document.querySelector('button').addEventListener('click', function () {
        // Simulación de búsqueda
        const tbody = document.getElementById('tablaResultados');
        tbody.innerHTML = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">11/01/2026 14:30</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Pago Cuota #1 (Préstamo 6)</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">L. 1,500.00</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Admin</td>
                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aplicado</span></td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-red-600 hover:text-red-900" onclick="confirmarReversion(123)">
                        <i class="fas fa-undo-alt"></i> Revertir
                    </button>
                </td>
            </tr>
        `;
    });

    function confirmarReversion(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción anulará el pago y dejará constancia de la reversión. El dinero se deducirá de la caja del usuario.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, revertir transacción'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    'Revertido',
                    'La transacción ha sido marcada como revertida.',
                    'success'
                )
            }
        })
    }
</script>

<?php include 'includes/footer.php'; ?>