<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

// Check permissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = Auth::checkSession();
$userId = $user['id_usuario'];
$userRole = $user['rol_nombre'];

// Fetch authorized loans: 'Listo para Entrega' AND assigned to me
$db = getDB();

// Build query
$sql = "SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion,
c.id_agencia
FROM prestamos p
JOIN clientes c ON p.id_cliente = c.id
WHERE p.estado = 'Listo para Entrega'";

$params = [];

// If not Admin, filter by assignee
// If not Admin, filter by assignee
if ($userRole !== 'Administrador' && $userRole !== 'Gerente General') {
    // Privacy: Only show loans from my agency
    $sessionAgencia = $_SESSION['id_agencia'] ?? 0;
    $sql .= " AND c.id_agencia = ?";
    $params[] = $sessionAgencia;

    // Assignment: Only show loans assigned to me
    $sql .= " AND p.oficial_desembolsos_id = ?";
    $params[] = $userId;
}

$sql .= " ORDER BY p.updated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Desembolsos";
require_once __DIR__ . '/includes/layout.php';
?>

<div class="container mx-auto">
    <!-- Contenido ya dentro de main por layout.php -->
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-hand-holding-usd mr-2"></i>Bandeja de Desembolsos
    </h1>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Pendientes de Entrega</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php echo count($prestamos); ?>
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full text-green-600">
                    <i class="fas fa-money-bill-wave fa-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (!empty($prestamos)): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto a
                            Entregar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($prestamos as $loan): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">
                                    <?php echo htmlspecialchars($loan['nombre_completo']); ?>
                                </div>
                                <div class="text-xs text-gray-500">DNI:
                                    <?php echo htmlspecialchars($loan['numero_documento']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    L
                                    <?php echo number_format($loan['neto_entregar'] ?? $loan['monto_capital'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                L
                                <?php echo number_format($loan['valor_cuota'], 2); ?>
                                <span class="text-xs text-gray-500 block">
                                    <?php echo $loan['modalidad']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex space-x-2 justify-end">
                                    <button onclick='openDelivery(<?php echo json_encode($loan); ?>)'
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded shadow transition">
                                        <i class="fas fa-check-circle mr-1"></i> Entregar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                <p>No tienes desembolsos pendientes asignados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Delivery -->
<div id="modalDelivery" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
    style="z-index: 50;">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">Confirmación de Entrega de Efectivo</h3>
            <button onclick="closeDelivery()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Info Principal -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 text-center">
            <p class="text-gray-500 text-sm uppercase tracking-wide font-bold">Monto Efectivo a Entregar</p>
            <p class="text-4xl font-extrabold text-green-600 my-2" id="deliveryAmount">L 0.00</p>
            <p class="text-gray-600 font-medium" id="deliveryClient">Cliente</p>
        </div>

        <!-- Plan de Pagos Preview -->
        <div class="mb-6">
            <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1">Plan de Pagos Autorizado</h4>
            <div id="cuotasPreview" class="bg-white border rounded h-48 overflow-y-auto text-sm">
                <!-- Cuotas here -->
            </div>
        </div>

        <!-- Legal Warning -->
        <div class="bg-yellow-50 p-3 rounded text-xs text-yellow-800 mb-6 flex items-start">
            <i class="fas fa-exclamation-triangle mt-1 mr-2"></i>
            <p>Al confirmar, usted certifica que está entregando el dinero efectivo al cliente identificado, quien firma
                recibo conforme. Esta acción activará el préstamo y no se puede deshacer.</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center pt-4 border-t">
            <button onclick="printReceipt()" id="btnPrintReceipt"
                class="hidden text-blue-600 hover:text-blue-800 font-bold">
                <i class="fas fa-print mr-1"></i> Imprimir Recibo
            </button>
            <div class="flex space-x-3 ml-auto">
                <button onclick="closeDelivery()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</button>
                <button id="btnConfirmDelivery" onclick="confirmDelivery()"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow transform hover:scale-105 transition">
                    <i class="fas fa-check-double mr-2"></i> CONFIRMAR ENTREGA
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let currentLoan = null;

    function openDelivery(loan) {
        currentLoan = loan;

        // Populate Data
        document.getElementById('deliveryClient').textContent = loan.nombre_completo;
        const monto = parseFloat(loan.neto_entregar || loan.monto_capital);
        document.getElementById('deliveryAmount').textContent = 'L ' + monto.toLocaleString('es-HN', { minimumFractionDigits: 2 });

        // Reset Buttons
        document.getElementById('btnConfirmDelivery').disabled = false;
        document.getElementById('btnConfirmDelivery').classList.remove('opacity-50', 'cursor-not-allowed');
        document.getElementById('btnPrintReceipt').classList.remove('hidden'); // Available always? Or after? User said "Muestra una opcion... al confirmar". Let's show it always so they can sign BEFORE confirm.

        // Load Cuotas
        loadCuotas(loan.id);

        $('#modalDelivery').removeClass('hidden');
    }

    async function loadCuotas(id) {
        const container = document.getElementById('cuotasPreview');
        container.innerHTML = '<p class="p-4 text-center text-gray-500">Cargando...</p>';
        try {
            const res = await fetch(`${BASE_URL}/app/api/prestamos/cuotas.php?prestamo_id=${id}`);
            const data = await res.json();
            if (data.success && data.data.cuotas) {
                let html = '<table class="w-full text-left"><thead><tr class="bg-gray-100 text-xs text-gray-600"><th class="p-2">#</th><th class="p-2">Fecha</th><th class="p-2 text-right">Monto</th></tr></thead><tbody>';
                data.data.cuotas.forEach(c => {
                    const [y, m, d] = c.fecha_vencimiento.split('-');
                    html += `<tr class="border-b text-xs hover:bg-gray-50">
                        <td class="p-2">${c.numero_cuota}</td>
                        <td class="p-2">${d}/${m}/${y}</td>
                        <td class="p-2 text-right">L ${parseFloat(c.monto_cuota).toFixed(2)}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            }
        } catch (e) {
            container.innerHTML = '<p class="text-red-500 p-2">Error cargando cuotas</p>';
        }
    }

    function closeDelivery() {
        $('#modalDelivery').addClass('hidden');
        currentLoan = null;
    }

    function printReceipt() {
        if (!currentLoan) return;
        window.open(`${BASE_URL}/public/admin/print_docs.php?type=recibo_entrega&id=${currentLoan.id}`, '_blank');
    }

    async function confirmDelivery() {
        if (!currentLoan) return;

        const result = await Swal.fire({
            title: '¿Confirmar Entrega?',
            text: "El préstamo pasará a estado Activo. Asegúrese de haber entregado el dinero y tener el recibo firmado.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            confirmButtonText: 'Sí, Confirmar Entrega'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`${BASE_URL}/app/api/prestamos/update_status.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prestamo_id: currentLoan.id,
                        nuevo_estado: 'Activo'
                    })
                });
                const res = await response.json();

                if (res.success) {
                    // Show Receipt Option Again just in case
                    await Swal.fire({
                        title: '¡Entrega Exitosa!',
                        text: 'El préstamo está ahora activo.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Imprimir Comprobante',
                        cancelButtonText: 'Cerrar'
                    }).then((r) => {
                        if (r.isConfirmed) {
                            printReceipt();
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Fallo de conexión', 'error');
            }
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>