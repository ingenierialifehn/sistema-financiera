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
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>

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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cliente
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
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay préstamos pendientes de
                            formalización.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">
                                    <?php echo htmlspecialchars($row['nombre_completo']); ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['numero_documento']); ?>
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

        <!-- Vista Previa del Calendario de Cuotas -->
        <div class="mb-6 bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i> Vista Previa del Plan de Pagos
            </h4>
            <div id="vistaPreviewCuotas" class="bg-white rounded p-3 max-h-60 overflow-y-auto">
                <p class="text-gray-500 text-center">Cargando calendario...</p>
            </div>
            <p class="text-xs text-blue-600 mt-2">
                <i class="fas fa-info-circle"></i> Este calendario fue generado automáticamente por el Analista
            </p>
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

        // Keywords for Asesor (solo Asesores de Crédito)
        populateSelect(asesorSelect, ['asesor', 'cobrador']);

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

        // Load cuotas preview
        loadCuotasPreview(loan.id);

        validateChecklist();
        $('#modalFormalize').removeClass('hidden');
    }

    async function loadCuotasPreview(prestamoId) {
        const container = document.getElementById('vistaPreviewCuotas');
        container.innerHTML = '<p class="text-gray-500 text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';

        try {
            const response = await fetch(`<?php echo BASE_URL; ?>/app/api/prestamos/cuotas.php?prestamo_id=${prestamoId}`);
            const result = await response.json();

            if (result.success && result.data.cuotas && result.data.cuotas.length > 0) {
                const cuotas = result.data.cuotas;
                let html = '<table class="min-w-full text-xs">';
                html += '<thead class="bg-gray-100"><tr>';
                html += '<th class="px-2 py-1 text-left">#</th>';
                html += '<th class="px-2 py-1 text-left">Fecha Vencimiento</th>';
                html += '<th class="px-2 py-1 text-right">Monto</th>';
                html += '</tr></thead><tbody>';

                cuotas.forEach((cuota, idx) => {
                    // Fix timezone issue: manual format from YYYY-MM-DD
                    const [y, m, d] = cuota.fecha_vencimiento.split('-');
                    const fecha = `${d}/${m}/${y}`;

                    const monto = parseFloat(cuota.monto_cuota).toLocaleString('es-HN', { minimumFractionDigits: 2 });
                    const bgClass = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                    html += `<tr class="${bgClass}">`;
                    html += `<td class="px-2 py-1">${cuota.numero_cuota}</td>`;
                    html += `<td class="px-2 py-1">${fecha}</td>`;
                    html += `<td class="px-2 py-1 text-right">L ${monto}</td>`;
                    html += '</tr>';
                });

                html += '</tbody></table>';
                html += `<p class="text-xs text-gray-600 mt-2 text-center">Total de ${cuotas.length} cuotas</p>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-orange-600 text-center"><i class="fas fa-exclamation-triangle"></i> No se encontraron cuotas generadas</p>';
            }
        } catch (e) {
            console.error('Error loading cuotas:', e);
            container.innerHTML = '<p class="text-red-600 text-center"><i class="fas fa-times"></i> Error al cargar cuotas</p>';
        }
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