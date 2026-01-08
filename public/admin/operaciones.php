<?php
/**
 * Operaciones: Formalización de Préstamos
 */

$pageTitle = 'Operaciones - Formalización';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('operaciones');
require_once __DIR__ . '/includes/layout.php';

// Check for opened cash box (still good practice)
if (session_status() === PHP_SESSION_NONE) session_start();
$idAgencia = $_SESSION['id_agencia'] ?? Auth::getCurrentUser()['id_agencia'];
$nombreAgencia = isset($_SESSION['nombre_agencia']) ? $_SESSION['nombre_agencia'] : 'Sin Agencia';

// Fetch Loans in "Pendiente de Operaciones"
require_once __DIR__ . '/../../app/config/database.php';
$db = getDB();

$sql = "SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion 
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        WHERE p.estado = 'Pendiente de Operaciones' 
        AND c.id_agencia = ?
        ORDER BY p.updated_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$idAgencia]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch loans in delivery route (estado = 'Listo para Entrega')
$sqlRuta = "SELECT p.*, c.nombre_completo, c.numero_documento, 
            u.username as ruta_usuario_nombre
            FROM prestamos p
            JOIN clientes c ON p.id_cliente = c.id
            LEFT JOIN usuarios u ON p.ruta_usuario_id = u.id_usuario
            WHERE p.estado = 'Listo para Entrega'
            AND c.id_agencia = ?
            ORDER BY p.updated_at DESC";

$stmtRuta = $db->prepare($sqlRuta);
$stmtRuta->execute([$idAgencia]);
$prestamosRuta = $stmtRuta->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Formalización de Préstamos</h1>
            <p class="text-gray-600">Bandeja de preparación de documentación legal para <?php echo htmlspecialchars($nombreAgencia); ?></p>
        </div>
        <button onclick="location.reload()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded shadow">
            <i class="fas fa-sync-alt mr-2"></i>Actualizar
        </button>
    </div>

    <!-- En Ruta Section -->
    <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-orange-800 mb-4">
            <i class="fas fa-route mr-2"></i>Préstamos en Ruta de Desembolso
        </h2>
        <?php if (!empty($prestamosRuta)): 
            // Group by user
            $rutasPorUsuario = [];
            foreach ($prestamosRuta as $pr) {
                $userId = $pr['ruta_usuario_id'] ?? 0;
                $userName = $pr['ruta_usuario_nombre'] ?? 'Sin Asignar';
                if (!isset($rutasPorUsuario[$userId])) {
                    $rutasPorUsuario[$userId] = [
                        'nombre' => $userName,
                        'prestamos' => [],
                        'total_dinero' => 0,
                        'cantidad' => 0
                    ];
                }
                $rutasPorUsuario[$userId]['prestamos'][] = $pr;
                $rutasPorUsuario[$userId]['total_dinero'] += ($pr['neto_entregar'] ?? $pr['monto_capital']);
                $rutasPorUsuario[$userId]['cantidad']++;
            }
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($rutasPorUsuario as $ruta): ?>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="bg-orange-100 rounded-full p-2 mr-3">
                                <i class="fas fa-user text-orange-600"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($ruta['nombre']); ?></p>
                                <p class="text-xs text-gray-500">En ruta</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Dinero en ruta:</span>
                            <span class="font-bold text-orange-600">L <?php echo number_format($ruta['total_dinero'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Préstamos:</span>
                            <span class="font-semibold text-gray-700"><?php echo $ruta['cantidad']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No hay préstamos en ruta actualmente.</p>
            <p class="text-sm text-gray-400 mt-1">Los préstamos aparecerán aquí cuando sean enviados a desembolso.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Loan List -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Neto a Entregar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Plazo / Mod.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cuota</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Acción</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($prestamos)): ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay préstamos pendientes de formalización.</td></tr>
                <?php else: ?>
                    <?php foreach($prestamos as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['nombre_completo']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['numero_documento']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-blue-600">
                                L <?php echo number_format($row['monto_capital'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-green-600">
                                L <?php echo number_format($row['neto_entregar'] ?? $row['monto_capital'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo $row['plazo_meses']; ?> meses / <?php echo $row['modalidad']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-700">
                                L <?php echo number_format($row['valor_cuota'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='openFormalize(<?php echo json_encode($row); ?>)' 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm shadow">
                                    <i class="fas fa-file-contract mr-1"></i> Formalizar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary Totals -->
    <?php if (!empty($prestamos)): 
        $totalMonto = array_sum(array_column($prestamos, 'monto_capital'));
        $totalNeto = array_sum(array_map(function($p) { 
            return $p['neto_entregar'] ?? $p['monto_capital']; 
        }, $prestamos));
    ?>
    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Créditos Aprobados</p>
                    <p class="text-2xl font-bold text-blue-600">L <?php echo number_format($totalMonto, 2); ?></p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-hand-holding-usd text-2xl text-blue-600"></i>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Neto a Entregar</p>
                    <p class="text-2xl font-bold text-green-600">L <?php echo number_format($totalNeto, 2); ?></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Formalización -->
<div id="modalFormalize" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 50;">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-xl font-bold text-gray-800">Formalización de Expediente</h3>
            <button onclick="closeModalFormalize()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left: Document Generation -->
            <div>
                <h4 class="font-semibold text-gray-700 mb-3 border-b">1. Generar Documentación</h4>
                <div class="space-y-3">
                    <button onclick="printDoc('contrato')" class="w-full flex items-center justify-between p-3 bg-gray-50 border rounded hover:bg-blue-50 transition group">
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Contrato de Préstamo</span>
                        <i class="fas fa-print text-gray-400 group-hover:text-blue-600"></i>
                    </button>
                    <button onclick="printDoc('pagare')" class="w-full flex items-center justify-between p-3 bg-gray-50 border rounded hover:bg-blue-50 transition group">
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Pagaré</span>
                        <i class="fas fa-print text-gray-400 group-hover:text-blue-600"></i>
                    </button>
                    <button onclick="printDoc('plan')" class="w-full flex items-center justify-between p-3 bg-gray-50 border rounded hover:bg-blue-50 transition group">
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Plan de Pagos (Calendario)</span>
                        <i class="fas fa-print text-gray-400 group-hover:text-blue-600"></i>
                    </button>
                </div>
            </div>

            <!-- Right: Checklist Validation -->
            <div>
                <h4 class="font-semibold text-gray-700 mb-3 border-b">2. Validación de Expediente</h4>
                <div class="space-y-2">
                    <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" id="check_dni" class="form-checkbox h-5 w-5 text-indigo-600" onchange="validateChecklist()">
                        <span class="text-sm text-gray-700">DNI Físico / Copia</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" id="check_recibo" class="form-checkbox h-5 w-5 text-indigo-600" onchange="validateChecklist()">
                        <span class="text-sm text-gray-700">Copia Recibo Servicio</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" id="check_negocio" class="form-checkbox h-5 w-5 text-indigo-600" onchange="validateChecklist()">
                        <span class="text-sm text-gray-700">Fotos de Negocio</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" id="check_contrato" class="form-checkbox h-5 w-5 text-indigo-600" onchange="validateChecklist()">
                        <span class="text-sm text-gray-700 text-red-600 font-bold">Contrato Firmado</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t flex justify-end">
            <button id="btnSendToDelivery" onclick="sendToDelivery()" disabled 
                class="bg-gray-300 text-gray-500 px-6 py-2 rounded font-bold cursor-not-allowed transition">
                <i class="fas fa-paper-plane mr-2"></i> Enviar a Desembolso
            </button>
        </div>
    </div>
</div>

<script>
    let currentLoan = null;

    function openFormalize(loan) {
        currentLoan = loan;
        // Reset Modal
        document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        validateChecklist();
        $('#modalFormalize').removeClass('hidden');
    }

    function closeModalFormalize() {
        $('#modalFormalize').addClass('hidden');
        currentLoan = null;
    }

    function validateChecklist() {
        const checks = ['check_dni', 'check_recibo', 'check_negocio', 'check_contrato'];
        const allChecked = checks.every(id => document.getElementById(id).checked);
        const btn = document.getElementById('btnSendToDelivery');
        
        if (allChecked) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
        }
    }

    function printDoc(type) {
        if (!currentLoan) return;
        // Open print view in new window
        window.open(`<?php echo BASE_URL; ?>/public/admin/print_docs.php?type=${type}&id=${currentLoan.id}`, '_blank');
    }

    async function sendToDelivery() {
        if (!currentLoan) return;
        
        const confirm = await Swal.fire({
            title: '¿Confirmar Envío?',
            text: "El préstamo pasará a estado 'Listo para Entrega' y el cajero podrá desembolsarlo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Enviar'
        });

        if (confirm.isConfirmed) {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/update_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        prestamo_id: currentLoan.id,
                        nuevo_estado: 'Listo para Entrega'
                    })
                });
                const res = await response.json();
                
                if (res.success) {
                    await Swal.fire('¡Enviado!', 'El expediente ha sido enviado a caja.', 'success');
                    location.reload();
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