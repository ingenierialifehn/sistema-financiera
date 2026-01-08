<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); 

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $data = $_POST;

    // Validations
    if (empty($data['cliente_id']) || empty($data['monto']) || empty($data['plazo_meses']) || empty($data['modalidad'])) {
        throw new Exception("Todos los campos son obligatorios");
    }

    $clienteId = intval($data['cliente_id']);
    $monto = floatval($data['monto']);
    $plazoMeses = intval($data['plazo_meses']);
    $modalidad = $data['modalidad'];

    $db = getDB();

    // Start Transaction
    $db->beginTransaction();

    // 1. Check Active Loan
    // We strictly follow the request: verify if client has an 'Activo' loan.
    $stmt = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE id_cliente = ? AND estado = 'Activo'");
    $stmt->execute([$clienteId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El cliente ya posee un préstamo activo.");
    }

    // Also, logically we should probably check if they already have a pending request ('Solicitado') to avoid duplicates?
    // The user didn't explicitly ask for this, but it's good practice. I'll stick to the requested check to avoid friction, 
    // or maybe add 'Solicitado' based on previous logic attempt.
    // Let's stick to just blocking 'Activo' as per explicit instructions to "verify if client has 'Activo' loan".

    // 2. Financial Calculations
    $tasaTotal = 11.00;
    $tasaInteres = 4.00;
    $tasaGastos = 4.00;
    $tasaComision = 3.00;

    $totalInteresMonto = $monto * ($tasaTotal / 100) * $plazoMeses;
    $totalAPagar = $monto + $totalInteresMonto;

    // Calculate number of quotas for estimation logic (stored in DB) but NOT generating specific dates yet
    // We still need 'valor_cuota' for the record
    $numCuotas = 0;
    switch ($modalidad) {
        case 'Diario':
            // "Genera 20 cuotas por cada mes" rule for estimation
            $numCuotas = $plazoMeses * 20;
            break;
        case 'Semanal':
            $numCuotas = $plazoMeses * 4;
            break;
        case 'Catorcenal':
            $numCuotas = $plazoMeses * 2;
            break;
        case 'Mensual':
            $numCuotas = $plazoMeses * 1;
            break;
        default:
            throw new Exception("Modalidad inválida");
    }

    $valorCuota = $totalAPagar / $numCuotas;

    // Obtener Asesor (Usuario Logueado)
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $asesorId = $_SESSION['id_usuario'] ?? null;

    // 3. Insert Loan Record with status 'Solicitado'
    $sql = "INSERT INTO prestamos (
        id_cliente, asesor_creditos_id, monto_capital, modalidad, plazo_meses, 
        tasa_total, tasa_interes, tasa_gastos, tasa_comision,
        valor_cuota, total_a_pagar, neto_entregar, estado, fecha_solicitud
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?,
        ?, ?, ?, 'Solicitado', NOW()
    )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $clienteId,
        $asesorId,
        $monto,
        $modalidad,
        $plazoMeses,
        $tasaTotal,
        $tasaInteres,
        $tasaGastos,
        $tasaComision,
        $valorCuota,
        $totalAPagar,
        $monto  // neto_entregar = monto_capital initially
    ]);

    $prestamoId = $db->lastInsertId();

    // REMOVED: Fund Deduction logic (User Request: "NO debe restar dinero")
    // REMOVED: Schedule Generation logic (User Request: "ni generar el plan de pagos todavía")

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Solicitud de crédito registrada correctamente.',
        'prestamo_id' => $prestamoId
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>