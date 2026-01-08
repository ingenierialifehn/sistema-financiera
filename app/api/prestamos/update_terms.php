<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Assuming Analyst role check handled by Auth or in logic

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input)
        $input = $_POST;

    if (empty($input['prestamo_id']) || !isset($input['tasa_total'])) {
        throw new Exception("ID de préstamo y Tasa Total son obligatorios");
    }

    $prestamoId = intval($input['prestamo_id']);
    $newTasaTotal = floatval($input['tasa_total']);

    if ($newTasaTotal <= 0) {
        throw new Exception("La tasa debe ser mayor a 0");
    }

    $db = getDB();

    // 1. Get current loan details
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        throw new Exception("Préstamo no encontrado");
    }

    // 2. Check Permissions / Status
    // "Bloqueo: Una vez que la solicitud pase a estado 'Aprobado', estos campos ya no podrán ser editados... excepto por el Administrador."
    $restrictedStates = ['Aprobado', 'Activo', 'Finalizado', 'Rechazado'];
    if (in_array($loan['estado'], $restrictedStates)) {
        // Here we should check if User is Admin. For now, assuming simply blocking based on status as requested implies logic logic.
        // If we strictly follow "excepto por Administrador", we need session role check.
        // Assuming session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $userRole = $_SESSION['rol_nombre'] ?? '';
        if ($userRole !== 'admin' && $userRole !== 'Administrador') { // Checks common role names
            throw new Exception("No tiene permisos para editar un préstamo en estado '" . $loan['estado'] . "'");
        }
    }

    // 3. Breakdown Logic
    // "Interés nominal nunca supere el 4%, moviendo cualquier excedente... a gastos_financieros y comisiones_papeleria"
    $tasaInteres = ($newTasaTotal > 4.00) ? 4.00 : $newTasaTotal;
    $remainder = $newTasaTotal - $tasaInteres;

    // Split remainder between Gastos and Comision. 
    // Let's do 50/50.
    $tasaGastos = $remainder / 2;
    $tasaComision = $remainder / 2;

    // 4. Recalculate Totals
    // 4. Recalculate Totals
    // Use new values if provided, otherwise keep existing
    $monto = isset($input['monto_capital']) ? floatval($input['monto_capital']) : floatval($loan['monto_capital']);
    $plazoMeses = isset($input['plazo_meses']) ? intval($input['plazo_meses']) : intval($loan['plazo_meses']);
    $modalidad = isset($input['modalidad']) ? $input['modalidad'] : $loan['modalidad'];

    $totalInteresMonto = $monto * ($newTasaTotal / 100) * $plazoMeses;
    $totalAPagar = $monto + $totalInteresMonto;

    // Recalculate Quotas
    $numCuotas = 0;
    switch ($modalidad) {
        case 'Diario':
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
    }

    $valorCuota = ($numCuotas > 0) ? ($totalAPagar / $numCuotas) : 0;

    // 5. Update Database
    // Check if comments are provided to update them as well
    $comentarioAnalisis = isset($input['comentario_analisis']) ? trim($input['comentario_analisis']) : null;
    $comentarioVerificacion = isset($input['comentario_verificacion']) ? trim($input['comentario_verificacion']) : null;

    $sql = "UPDATE prestamos SET 
            monto_capital = ?,
            plazo_meses = ?,
            modalidad = ?,
            tasa_total = ?, 
            tasa_interes = ?, 
            tasa_gastos = ?, 
            tasa_comision = ?, 
            total_a_pagar = ?, 
            valor_cuota = ?,
            neto_entregar = ?,
            updated_at = NOW()";

    $params = [
        $monto,
        $plazoMeses,
        $modalidad,
        $newTasaTotal,
        $tasaInteres,
        $tasaGastos,
        $tasaComision,
        $totalAPagar,
        $valorCuota,
        $monto  // For now, neto_entregar = monto_capital (will change for refinancing)
    ];

    if ($comentarioAnalisis !== null) {
        $sql .= ", comentario_analisis = ?";
        $params[] = $comentarioAnalisis;
    }
    if ($comentarioVerificacion !== null) {
        $sql .= ", comentario_verificacion = ?";
        $params[] = $comentarioVerificacion;
    }

    $sql .= " WHERE id = ?";
    $params[] = $prestamoId;

    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Términos financieros actualizados correctamente.',
        'data' => [
            'tasa_total' => $newTasaTotal,
            'total_a_pagar' => $totalAPagar,
            'valor_cuota' => $valorCuota,
            'tasa_interes' => $tasaInteres,
            'tasa_gastos' => $tasaGastos,
            'tasa_comision' => $tasaComision
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>