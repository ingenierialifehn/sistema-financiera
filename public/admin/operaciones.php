<?php
/**
 * Operaciones: Formalización de Préstamos
 */

$pageTitle = 'Operaciones - Formalización';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('operaciones');
require_once __DIR__ . '/includes/layout.php';

// Check for opened cash box (still good practice)
if (session_status() === PHP_SESSION_NONE)
    session_start();
$idAgencia = $_SESSION['id_agencia'] ?? Auth::getCurrentUser()['id_agencia'];
$nombreAgencia = isset($_SESSION['nombre_agencia']) ? $_SESSION['nombre_agencia'] : 'Sin Agencia';

// Fetch Loans in "Pendiente de Operaciones"
require_once __DIR__ . '/../../app/config/database.php';
$db = getDB();

$sql = "SELECT p.*, c.nombre_completo, c.numero_documento, c.direccion, c.id_agencia,
        p.asesor_creditos_id, p.oficial_desembolsos_id,
        u1.username as asesor_nombre,
        u2.username as oficial_nombre
        FROM prestamos p
        JOIN clientes c ON p.id_cliente = c.id
        LEFT JOIN usuarios u1 ON p.asesor_creditos_id = u1.id_usuario
        LEFT JOIN usuarios u2 ON p.oficial_desembolsos_id = u2.id_usuario
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
            <p class="text-gray-600">Bandeja de preparación de documentación legal para
                <?php echo htmlspecialchars($nombreAgencia); ?>
            </p>
        </div>
        <button onclick="location.reload()"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded shadow">
            <i class="fas fa-sync-alt mr-2"></i>Actualizar
        </button>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulkActions"
        class="hidden bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6 flex justify-between items-center shadow-sm">
        <div class="flex items-center">
            <span class="bg-indigo-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold mr-3"
                id="selectedCount">0</span>
            <span class="text-indigo-900 font-medium">préstamos seleccionados</span>
        </div>
        <div class="flex space-x-3">
            <button onclick="openBulkAssign('asesor')"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm font-medium transition">
                <i class="fas fa-user-tie mr-2"></i> Asignar Asesor
            </button>
            <button onclick="openBulkAssign('oficial')"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded shadow text-sm font-medium transition">
                <i class="fas fa-hand-holding-usd mr-2"></i> Asignar Desembolsador
            </button>
            <div class="h-6 w-px bg-indigo-300 mx-2"></div>
            <button onclick="bulkFormalize()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm font-bold transition flex items-center">
                <i class="fas fa-paper-plane mr-2"></i> Formalizar Seleccionados
            </button>
            <div class="h-6 w-px bg-indigo-300 mx-2"></div>
            <button onclick="bulkPrintMenu()"
                class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded shadow text-sm font-medium transition flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir
            </button>
        </div>
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
                                <span class="font-bold text-orange-600">L
                                    <?php echo number_format($ruta['total_dinero'], 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Préstamos:</span>
                                <span class="font-semibold text-gray-700"><?php echo $ruta['cantidad']; ?></span>
                            </div>

                            <!-- Listado Detallado de Préstamos -->
                            <div class="mt-3 border-t pt-2">
                                <p class="text-xs font-semibold text-gray-500 mb-2 uppercase">Detalle de Asignaciones:</p>
                                <ul class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <?php foreach ($ruta['prestamos'] as $p): ?>
                                        <li
                                            class="flex justify-between items-start text-xs bg-orange-50 p-2 rounded border border-orange-100">
                                            <div class="flex-1 mr-2">
                                                <span class="font-medium text-gray-800 block truncate"
                                                    title="<?php echo htmlspecialchars($p['nombre_completo']); ?>">
                                                    <?php echo htmlspecialchars($p['nombre_completo']); ?>
                                                </span>
                                                <span class="text-gray-500 text-[10px]">#<?php echo $p['id']; ?></span>
                                            </div>
                                            <span class="font-bold text-orange-700 whitespace-nowrap">
                                                L <?php echo number_format($p['neto_entregar'] ?? $p['monto_capital'], 2); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
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
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                            class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Personal
                        Asignado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Monto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Neto a
                        Entregar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Plazo /
                        Mod.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cuota
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Acción
                    </th>
                </tr>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No hay préstamos pendientes de
                            formalización.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input type="checkbox"
                                    class="loan-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                    value="<?php echo $row['id']; ?>" data-asesor="<?php echo $row['asesor_creditos_id']; ?>"
                                    data-oficial="<?php echo $row['oficial_desembolsos_id']; ?>" onchange="updateBulkActions()">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">
                                    <?php echo htmlspecialchars($row['nombre_completo']); ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['numero_documento']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs space-y-1">
                                    <div class="flex items-center" title="Asesor de Créditos">
                                        <i class="fas fa-user-tie text-indigo-500 w-4"></i>
                                        <span
                                            class="<?php echo $row['asesor_nombre'] ? 'text-gray-700 font-medium' : 'text-red-400 italic'; ?>">
                                            <?php echo $row['asesor_nombre'] ?? 'Sin asignar'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center" title="Oficial de Desembolsos">
                                        <i class="fas fa-hand-holding-usd text-teal-500 w-4"></i>
                                        <span
                                            class="<?php echo $row['oficial_nombre'] ? 'text-gray-700 font-medium' : 'text-red-400 italic'; ?>">
                                            <?php echo $row['oficial_nombre'] ?? 'Sin asignar'; ?>
                                        </span>
                                    </div>
                                </div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 justify-end">
                                    <button onclick="openPrintOptions(<?php echo $row['id']; ?>)"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1 rounded text-sm shadow border border-gray-300"
                                        title="Imprimir Documentos">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button onclick='openFormalize(<?php echo json_encode($row); ?>)'
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm shadow flex items-center">
                                        Formalizar <i class="fas fa-arrow-right ml-1"></i>
                                    </button>
                                </div>
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
        $totalNeto = array_sum(array_map(function ($p) {
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
<div id="modalFormalize" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
    style="z-index: 50;">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white mb-10">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-xl font-bold text-gray-800">Formalización de Expediente</h3>
            <button onclick="closeModalFormalize()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Resumen del Préstamo (En lugar de cuotas vacías) -->
        <div class="mb-6 bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-file-invoice-dollar mr-2"></i> Resumen de Condiciones
            </h4>
            <div id="vistaResumenPrestamo" class="bg-white rounded p-4">
                <p class="text-gray-500 text-center">Cargando información...</p>
            </div>
        </div>



        <!-- Asignación de Personal -->
        <div class="mt-4">
            <h4 class="font-semibold text-gray-700 mb-3">Asignación de Personal</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Asesor de Créditos (Cobro) <span class="text-red-500">*</span>
                    </label>
                    <select id="asesor_creditos_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        onchange="validateChecklist()">
                        <option value="">Seleccione un asesor...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Oficial de Desembolsos (Entrega) <span class="text-red-500">*</span>
                    </label>
                    <select id="oficial_desembolsos_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        onchange="validateChecklist()">
                        <option value="">Seleccione un oficial...</option>
                    </select>
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
    let availableUsers = [];

    // Load available users on page load
    $(document).ready(function () {
        loadAvailableUsers();
    });

    async function loadAvailableUsers() {
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/app/api/usuarios/list.php');
            const result = await response.json();
            if (result.success) {
                availableUsers = result.data;
            }
        } catch (e) {
            console.error('Error loading users:', e);
        }
    }

    // --- Bulk Selection Logic ---
    function toggleSelectAll() {
        const isChecked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.loan-checkbox').forEach(cb => cb.checked = isChecked);
        updateBulkActions();
    }

    function updateBulkActions() {
        const selected = document.querySelectorAll('.loan-checkbox:checked').length;
        document.getElementById('selectedCount').innerText = selected;

        const bulkBar = document.getElementById('bulkActions');
        if (selected > 0) {
            bulkBar.classList.remove('hidden');
        } else {
            bulkBar.classList.add('hidden');
        }
    }

    async function openBulkAssign(type) {
        const selectedIds = Array.from(document.querySelectorAll('.loan-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        const roleName = type === 'asesor' ? 'Asesor de Créditos' : 'Oficial de Desembolsos';

        // Filter users for the dropdown
        let options = '<option value="">Seleccione un usuario...</option>';
        const keywords = type === 'asesor'
            ? ['asesor de creditos', 'asesor de crédito']
            : ['ofic. de desembolso', 'desembolsos', 'oficial de desembolso'];

        availableUsers.forEach(user => {
            // Simplified filter: just match keywords, agency check is implied but weak for bulk (assuming same agency context)
            const cleanPuesto = (user.puesto_cargo || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const cleanRol = (user.rol_nombre || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const isMatch = keywords.some(k => {
                const cleanKey = k.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                return cleanPuesto.includes(cleanKey) || cleanRol.includes(cleanKey);
            });

            if (isMatch && (!user.id_agencia || user.id_agencia == <?php echo $idAgencia; ?>)) {
                options += `<option value="${user.id_usuario}">${user.nombre_completo}</option>`;
            }
        });

        const { value: userId } = await Swal.fire({
            title: `Asignación Masiva: ${roleName}`,
            html: `
                <p class="mb-4 text-gray-600">Se asignará a ${selectedIds.length} préstamos seleccionados.</p>
                <select id="swal-input1" class="swal2-input">
                    ${options}
                </select>
            `,
            focusConfirm: false,
            showCancelButton: true,
            preConfirm: () => {
                return document.getElementById('swal-input1').value;
            }
        });

        if (userId) {
            // Check for potential overwrites
            let overwriteCount = 0;
            const fieldDate = type === 'asesor' ? 'data-asesor' : 'data-oficial';
            
            selectedIds.forEach(id => {
                const cb = document.querySelector(`.loan-checkbox[value="${id}"]`);
                const currentVal = cb.getAttribute(fieldDate);
                // If it has a value (not empty/0) AND it is different from the new value
                if (currentVal && currentVal != '0' && currentVal != userId) {
                    overwriteCount++;
                }
            });

            if (overwriteCount > 0) {
                const roleLabel = type === 'asesor' ? 'Asesor' : 'Desembolsador';
                const confirmOverwrite = await Swal.fire({
                    title: '¿Sobrescribir Asignaciones?',
                    text: `${overwriteCount} de los préstamos seleccionados ya tienen un ${roleLabel} asignado diferente al seleccionado. ¿Desea sobrescribirlos?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, Sobrescribir',
                    cancelButtonText: 'Cancelar'
                });

                if (!confirmOverwrite.isConfirmed) {
                    return;
                }
            }

            // Execute bulk update
            try {
                Swal.fire({
                    title: 'Asignando...',
                    html: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                // Execute sequentially to avoid overwhelming server or race conditions
                for (const id of selectedIds) {
                    await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/asignar_personal_field.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            prestamo_id: id,
                            field: type === 'asesor' ? 'asesor_creditos_id' : 'oficial_desembolsos_id',
                            value: userId
                        })
                    });
                }

                await Swal.fire({
                    icon: 'success',
                    title: '¡Asignación Completada!',
                    text: 'El personal ha sido actualizado.',
                    timer: 1500
                });
                location.reload();

            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Hubo un problema al procesar la solicitud', 'error');
            }
        }
    }

    async function bulkFormalize() {
        const checkboxes = document.querySelectorAll('.loan-checkbox:checked');
        if (checkboxes.length === 0) return;

        // Validation: All selected must have both roles assigned
        let missingInfo = 0;
        let missingIds = [];

        checkboxes.forEach(cb => {
            const hasAsesor = cb.getAttribute('data-asesor') && cb.getAttribute('data-asesor') != '0';
            const hasOficial = cb.getAttribute('data-oficial') && cb.getAttribute('data-oficial') != '0';

            if (!hasAsesor || !hasOficial) {
                missingInfo++;
                // Visual feedback could be added here (e.g., highlight row)
                cb.closest('tr').classList.add('bg-red-50');
            } else {
                cb.closest('tr').classList.remove('bg-red-50');
            }
        });

        if (missingInfo > 0) {
            Swal.fire({
                title: 'Información Incompleta',
                text: `${missingInfo} de los préstamos seleccionados no tienen Asesor o Desembolsador asignado. Por favor asígnelos antes de formalizar.`,
                icon: 'warning'
            });
            return;
        }

        const confirm = await Swal.fire({
            title: `Formalizar ${checkboxes.length} Préstamos`,
            text: "Todos los préstamos seleccionados pasarán a estado 'Listo para Entrega'.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669', // Green
            confirmButtonText: 'Sí, Formalizar Todos'
        });

        if (confirm.isConfirmed) {
            try {
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Enviando a desembolso...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                let errorCount = 0;
                let errorMessages = [];

                for (const cb of checkboxes) {
                    const id = cb.value;
                    const result = await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            prestamo_id: id,
                            nuevo_estado: 'Listo para Entrega'
                        })
                    });
                    const res = await result.json();
                    if(!res.success) {
                        errorCount++;
                        // Capture unique error messages
                        const msg = `Préstamo #${id}: ${res.message}`;
                        if(!errorMessages.includes(msg)) errorMessages.push(msg);
                    }
                }

                if (errorCount > 0) {
                    // Show detailed errors
                    await Swal.fire({
                        title: 'Atención',
                        html: `<div class="text-left text-sm text-red-600 max-h-40 overflow-y-auto">
                                <p class="mb-2 font-bold">El proceso completó con ${errorCount} errores:</p>
                                <ul class="list-disc pl-5">${errorMessages.map(m => `<li>${m}</li>`).join('')}</ul>
                               </div>`,
                        icon: 'warning'
                    });
                } else {
                    await Swal.fire('¡Éxito!', 'Todos los expedientes han sido enviados a desembolso.', 'success');
                }
                location.reload();

            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Error de conexión durante el proceso masivo', 'error');
            }
        }
    }

    async function bulkPrintMenu() {
        const selectedIds = Array.from(document.querySelectorAll('.loan-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) return;

        const { value: formValues } = await Swal.fire({
            title: 'Impresión Masiva',
            html: `
                <div class="text-left">
                    <p class="mb-3 text-gray-600">Seleccione los documentos a generar para los <b>${selectedIds.length} clientes</b> seleccionados:</p>
                    <label class="flex items-center space-x-2 mb-2 cursor-pointer">
                        <input type="checkbox" id="chk_contrato" class="form-checkbox h-5 w-5 text-indigo-600" checked>
                        <span>Contrato de Préstamo</span>
                    </label>
                    <label class="flex items-center space-x-2 mb-2 cursor-pointer">
                        <input type="checkbox" id="chk_pagare" class="form-checkbox h-5 w-5 text-indigo-600" checked>
                        <span>Pagaré</span>
                    </label>
                    <label class="flex items-center space-x-2 mb-2 cursor-pointer">
                        <input type="checkbox" id="chk_plan" class="form-checkbox h-5 w-5 text-indigo-600" checked>
                        <span>Plan de Pagos (Vista Previa)</span>
                    </label>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Generar PDF',
            preConfirm: () => {
                return {
                    contrato: document.getElementById('chk_contrato').checked,
                    pagare: document.getElementById('chk_pagare').checked,
                    plan: document.getElementById('chk_plan').checked
                }
            }
        });

        if (formValues) {
            // Build types param
            let types = [];
            if (formValues.contrato) types.push('contrato');
            if (formValues.pagare) types.push('pagare');
            if (formValues.plan) types.push('plan');

            if (types.length === 0) {
                Swal.fire('Error', 'Debe seleccionar al menos un documento', 'warning');
                return;
            }

            const idsParam = selectedIds.join(',');
            const typesParam = types.join(',');

            // Open new window with parameters
            window.open(`<?php echo BASE_URL; ?>/public/admin/print_docs_bulk.php?ids=${idsParam}&types=${typesParam}`, '_blank');
        }
    }
    // --- End Bulk Selection Logic ---

    function openPrintOptions(prestamoId) {
        Swal.fire({
            title: '<strong>Imprimir Documentos</strong>',
            icon: 'info',
            html:
                '<div class="flex flex-col gap-3 mt-4">' +
                `<button onclick="printDocDirect(${prestamoId}, 'contrato')" class="swal2-confirm swal2-styled" style="background-color: #4F46E5; margin: 0; width: 100%;">Contrato de Préstamo</button>` +
                `<button onclick="printDocDirect(${prestamoId}, 'pagare')" class="swal2-confirm swal2-styled" style="background-color: #4F46E5; margin: 0; width: 100%;">Pagaré</button>` +
                `<button onclick="printDocDirect(${prestamoId}, 'plan')" class="swal2-confirm swal2-styled" style="background-color: #4F46E5; margin: 0; width: 100%;">Plan de Pagos</button>` +
                '</div>',
            showConfirmButton: false,
            showCloseButton: true,
            focusConfirm: false
        });
    }

    function printDocDirect(id, type) {
        window.open(`<?php echo BASE_URL; ?>/public/admin/print_docs.php?type=${type}&id=${id}`, '_blank');
    }

    function openFormalize(loan) {
        currentLoan = loan;

        // Reset Modal
        document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);

        // Load users into selects with smart filtering
        const asesorSelect = document.getElementById('asesor_creditos_id');
        const oficialSelect = document.getElementById('oficial_desembolsos_id');

        asesorSelect.innerHTML = '<option value="">Seleccione un asesor...</option>';
        oficialSelect.innerHTML = '<option value="">Seleccione un oficial...</option>';

        // Helper to populate select
        const populateSelect = (select, keywords) => {
            let matches = [];

            availableUsers.forEach(user => {
                // Filtro de Agencia: Debe coincidir con la agencia del préstamo
                // Excepción: Si el usuario es admin (rol 1) podría ver todo, pero la regla es estricta.
                // Nota: Los valores vienen como strings o números, mejor usar comparacion laxa (==)
                if (user.id_agencia && user.id_agencia != currentLoan.id_agencia) {
                    return; // Saltar usuario de otra agencia
                }

                const text = `${user.nombre_completo} (${user.puesto_cargo || user.rol_nombre || 'Sin puesto'})`;
                // Normalizar texto para búsqueda
                const cleanPuesto = (user.puesto_cargo || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                const cleanRol = (user.rol_nombre || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                // Verificar coincidencia estricta con palabras clave
                const isMatch = keywords.some(k => {
                    const cleanKey = k.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    return cleanPuesto.includes(cleanKey) || cleanRol.includes(cleanKey);
                });

                if (isMatch) {
                    matches.push(`<option value="${user.id_usuario}">${text}</option>`);
                }
            });

            if (matches.length > 0) {
                select.innerHTML += matches.join('');
            } else {
                select.innerHTML += '<option value="" disabled>No se encontraron usuarios en esta agencia con este perfil</option>';
            }
        };

        // Keywords for Asesor (solo Asesores de Crédito - Estricto)
        // Antes: ['asesor', 'cobrador'] -> Ahora: requiere coincidencia más específica
        populateSelect(asesorSelect, ['asesor de creditos', 'asesor de crédito']);

        // Keywords for Oficial (solo Oficiales de Desembolso)
        // Incluimos 'ofic' y 'desembolso' para capturar "Ofic. de desembolsos"
        // 'caja' se mantiene por si acaso, pero priorizamos el puesto exacto
        populateSelect(oficialSelect, ['ofic. de desembolso', 'desembolsos', 'oficial de desembolso']);

        // Pre-select if already assigned
        if (loan.asesor_creditos_id) {
            asesorSelect.value = loan.asesor_creditos_id;
        }
        if (loan.oficial_desembolsos_id) {
            oficialSelect.value = loan.oficial_desembolsos_id;
        }

        // Render Loan Summary instead of missing quotas
        renderLoanSummary(loan);

        validateChecklist();
        $('#modalFormalize').removeClass('hidden');
    }

    function renderLoanSummary(loan) {
        const container = document.getElementById('vistaResumenPrestamo');

        const monto = parseFloat(loan.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 });
        const neto = parseFloat(loan.neto_entregar || loan.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 });
        const cuota = parseFloat(loan.valor_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 });
        const totalPagar = parseFloat(loan.total_a_pagar || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });

        // Calculate interest total roughly for display
        const totalInteres = (parseFloat(loan.total_a_pagar || 0) - parseFloat(loan.neto_entregar || loan.monto_capital));
        const interesDisplay = totalInteres > 0 ? 'L ' + totalInteres.toLocaleString('es-HN', { minimumFractionDigits: 2 }) : 'N/A';

        container.innerHTML = `
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Monto Solicitado</span>
                    <span class="block text-gray-900 font-bold text-lg">L ${monto}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Neto a Entregar</span>
                    <span class="block text-green-600 font-bold text-lg">L ${neto}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Valor Cuota</span>
                    <span class="block text-blue-600 font-bold text-lg">L ${cuota}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Modalidad</span>
                    <span class="block text-gray-800 font-semibold">${loan.modalidad}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Plazo</span>
                    <span class="block text-gray-800 font-semibold">${loan.plazo_meses} meses</span>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <span class="block text-gray-500 text-xs uppercase font-bold">Total a Pagar</span>
                    <span class="block text-gray-800 font-bold">L ${totalPagar}</span>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-500 text-center border-t pt-2">
                <i class="fas fa-info-circle text-blue-500"></i> 
                El plan de pagos detallado (calendario de cuotas) se generará automáticamente al momento del desembolso efectivo.
            </div>
        `;
    }

    function closeModalFormalize() {
        $('#modalFormalize').addClass('hidden');
        currentLoan = null;
    }

    function validateChecklist() {
        // Solo validamos asignación de personal
        const asesorSelected = document.getElementById('asesor_creditos_id').value !== '';
        const oficialSelected = document.getElementById('oficial_desembolsos_id').value !== '';

        const btn = document.getElementById('btnSendToDelivery');

        if (asesorSelected && oficialSelected) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
        }
    }



    async function sendToDelivery() {
        if (!currentLoan) return;

        const asesorId = document.getElementById('asesor_creditos_id').value;
        const oficialId = document.getElementById('oficial_desembolsos_id').value;

        const confirm = await Swal.fire({
            title: '¿Confirmar Envío?',
            text: "El préstamo pasará a estado 'Listo para Entrega' y el oficial de desembolsos podrá entregarlo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Enviar'
        });

        if (confirm.isConfirmed) {
            try {
                // First, assign personnel
                const assignResponse = await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/asignar_personal.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prestamo_id: currentLoan.id,
                        asesor_creditos_id: asesorId,
                        oficial_desembolsos_id: oficialId
                    })
                });
                const assignRes = await assignResponse.json();

                if (!assignRes.success) {
                    throw new Exception('Error al asignar personal: ' + assignRes.message);
                }

                // Then, update status
                const response = await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prestamo_id: currentLoan.id,
                        nuevo_estado: 'Listo para Entrega'
                    })
                });
                const res = await response.json();

                if (res.success) {
                    await Swal.fire('¡Enviado!', 'El expediente ha sido enviado a desembolso.', 'success');
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