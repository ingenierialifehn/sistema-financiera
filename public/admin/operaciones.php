<?php
/**
 * Operaciones de Agencia
 */

$pageTitle = 'Operaciones';
require_once __DIR__ . '/../auth_check.php';
requireViewPermission('operaciones');
require_once __DIR__ . '/includes/layout.php';

// Obtener nombre de la agencia del usuario
// Obtener nombre de la agencia del usuario
$user = Auth::getCurrentUser();

// Manejar cambio de agencia (Solo Super Administrador)
$esSuperAdmin = ($user['rol_nombre'] === 'Super Administrador' || $user['rol_nombre'] === 'Administrador'); // Ajustar según nombre exacto del rol
$mensajeCambio = '';

if ($esSuperAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_agencia_id'])) {
    $nuevaAgenciaId = intval($_POST['nueva_agencia_id']);

    // Obtener nombre de la nueva agencia
    $db = getDB();
    $stmtAgencia = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
    $stmtAgencia->execute([$nuevaAgenciaId]);
    $nuevaAgencia = $stmtAgencia->fetch(PDO::FETCH_ASSOC);

    if ($nuevaAgencia) {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $_SESSION['id_agencia'] = $nuevaAgenciaId;
        $_SESSION['nombre_agencia'] = $nuevaAgencia['nombre_agencia'];
        $mensajeCambio = "Cambiado a agencia: " . $nuevaAgencia['nombre_agencia'];

        // Redireccionar para limpiar POST
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Usar agencia de sesión o del usuario
if (session_status() === PHP_SESSION_NONE)
    session_start();
$idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

$nombreAgencia = 'Sin Agencia';
$cajaAbierta = false;

if ($idAgencia) {
    require_once __DIR__ . '/../../app/config/database.php';
    $db = getDB();

    // Obtener nombre de agencia
    $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
    $stmt->execute([$idAgencia]);
    $agencia = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreAgencia = $agencia ? $agencia['nombre_agencia'] : 'Sin Agencia';

    // Verificar si hay caja abierta para hoy
    $stmt = $db->prepare("
        SELECT id_control 
        FROM control_caja_diaria 
        WHERE id_agencia = ? 
        AND fecha_dia = CURDATE() 
        AND estado = 'Abierto'
    ");
    $stmt->execute([$idAgencia]);
    $cajaAbierta = $stmt->fetch() !== false;
}

// Si no hay caja abierta, mostrar mensaje de bloqueo
if (!$cajaAbierta) {
    ?>
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-lock text-6xl text-yellow-500"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-3">Módulo Bloqueado</h3>
            <p class="text-gray-600 mb-6">
                Debe abrir la caja del día en <strong>Control de Caja</strong> antes de realizar operaciones.
            </p>
            <a href="<?php echo base_url('public/admin/control_caja.php'); ?>"
                class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition">
                <i class="fas fa-cash-register mr-2"></i> Ir a Control de Caja
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Operaciones
            <?php echo htmlspecialchars($_SESSION['nombre_agencia'] ?? $nombreAgencia); ?>
        </h2>
        <p class="text-gray-600">Panel de control operativo de la agencia</p>
    </div>

    <?php if ($esSuperAdmin): ?>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <form method="POST" class="flex items-center gap-2">
                <label for="nueva_agencia_id" class="text-sm font-medium text-blue-800">Cambiar Agencia:</label>
                <select name="nueva_agencia_id" id="nueva_agencia_id" onchange="this.form.submit()"
                    class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <?php
                    // Cargar todas las agencias
                    $stmtTodas = $db->query("SELECT id_agencia, nombre_agencia FROM agencias WHERE estado = 'Activa' ORDER BY nombre_agencia");
                    $todasAgencias = $stmtTodas->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($todasAgencias as $ag):
                        ?>
                        <option value="<?php echo $ag['id_agencia']; ?>" <?php echo ($ag['id_agencia'] == $idAgencia) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ag['nombre_agencia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- Dashboard Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Bóveda -->
    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-100 text-sm mb-1">Bóveda</p>
                <h3 class="text-3xl font-bold" id="saldoBoveda">
                    <i class="fas fa-spinner fa-spin"></i>
                </h3>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-vault text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Clientes Totales -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm mb-1">Clientes Totales</p>
                <h3 class="text-3xl font-bold" id="clientesTotales">
                    <i class="fas fa-spinner fa-spin"></i>
                </h3>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-users text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Créditos Aprobados -->
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm mb-1">Créditos Aprobados</p>
                <h3 class="text-3xl font-bold" id="creditosAprobados">
                    <i class="fas fa-spinner fa-spin"></i>
                </h3>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Cartera en Calle -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm mb-1">Cartera en Calle</p>
                <h3 class="text-3xl font-bold" id="carteraEnCalle">
                    <i class="fas fa-spinner fa-spin"></i>
                </h3>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-hand-holding-usd text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bóveda Local -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Bóveda y Caja</h3>
            <p class="text-sm text-gray-600">Gestiona los fondos de la bóveda y caja de la agencia</p>
        </div>
        <!-- Modals removed - moved to control_caja.php -->

        <script src="<?php echo $baseUrl; ?>/public/admin/assets/js/operaciones.js?v=<?php echo time(); ?>"></script>
        <?php include __DIR__ . '/includes/footer.php'; ?>