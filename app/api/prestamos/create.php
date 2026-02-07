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

    // 1. Check Active Loan (Unless it's a refinancing request)
    $esRefinanciamiento = (!empty($data['es_refinanciamiento']) && ($data['es_refinanciamiento'] == '1' || $data['es_refinanciamiento'] === 'true'))
        || (isset($data['tipo_prestamo']) && $data['tipo_prestamo'] === 'Refinanciamiento');

    if (!$esRefinanciamiento) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE id_cliente = ? AND estado = 'Activo'");
        $stmt->execute([$clienteId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("El cliente ya posee un préstamo activo. Para refinanciar, utilice la opción correspondiente.");
        }
    }

    // ...

    // 2. Financial Calculations
    $tasaTotal = 11.00;
    $tasaInteres = 4.00;
    $tasaGastos = 4.00;
    $tasaComision = 3.00;

    $totalInteresMonto = $monto * ($tasaTotal / 100) * $plazoMeses;
    $totalAPagar = $monto + $totalInteresMonto;

    // ... (Cuotas logic same) ...
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
        default:
            throw new Exception("Modalidad inválida");
    }

    $valorCuota = $totalAPagar / $numCuotas;

    // Obtener Asesor
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $asesorId = $_SESSION['id_usuario'] ?? null;
    $observaciones = $data['observaciones'] ?? '';

    // 3. Insert Loan Record
    $tipo = $data['tipo_prestamo'] ?? 'Nuevo';
    // Validate custom types if needed, or rely on enum constraint
    if (!in_array($tipo, ['Nuevo', 'Refinanciamiento', 'Readecuacion', 'Represtamo'])) {
        $tipo = 'Nuevo';
    }

    // Check Auto-Approval (Excepcion Refinanciamiento)
    $estadoInicial = 'Solicitado';
    $autoApproved = false;
    $autoApproveMsg = "";

    if ($tipo === 'Refinanciamiento') {
        require_once __DIR__ . '/../../core/Helpers.php';
        $autoEnabled = getConfig('refinanciamiento_auto_approve_enabled', 0);

        if ($autoEnabled == 1) {
            // 1. Check Previous Active Loan
            $stmtPrev = $db->prepare("SELECT * FROM prestamos WHERE id_cliente = ? AND estado = 'Activo' ORDER BY id DESC LIMIT 1");
            $stmtPrev->execute([$clienteId]);
            $prevLoan = $stmtPrev->fetch(PDO::FETCH_ASSOC);

            if ($prevLoan) {
                // 2. Check Arrears (0 days)
                // Arrears are calculated based on pending cuotas with past due date
                $stmtArrears = $db->prepare("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = ? AND estado != 'pagada' AND fecha_vencimiento < CURDATE()");
                $stmtArrears->execute([$prevLoan['id']]);
                $arrearsCount = $stmtArrears->fetchColumn();

                // If 0 arrears count, imply 0 days delay (simplification, or calculate exact days)
                // User requirement: "lleva cero dias de atrasos" -> any overdue installment > 0 days.
                if ($arrearsCount == 0) {
                    // 3. Check Amount Increase %
                    // "aumento de 0 a 25% sobre el credito que tiene actualmente"
                    $oldAmount = floatval($prevLoan['monto_capital']);
                    $maxPercent = floatval(getConfig('refinanciamiento_auto_approve_max_increase_percent', 25));

                    if ($oldAmount > 0) {
                        $increasePercent = (($monto - $oldAmount) / $oldAmount) * 100;
                        if ($increasePercent >= 0 && $increasePercent <= $maxPercent) {
                            $estadoInicial = 'Listo para Entrega';
                            $autoApproved = true;
                            $autoApproveMsg = " [Auto-Aprobado por Regla de Excepción: 0 Atrasos, Aumento " . number_format($increasePercent, 2) . "%]";
                            $observaciones .= $autoApproveMsg;
                        }
                    }
                }
            }
        }
    }

    // --- CÁLCULO DE NETO A ENTREGAR (REFINANCIAMIENTO) ---
    $netoEntregar = $monto;
    $saldoAnterior = 0;

    if ($tipo === 'Refinanciamiento' || $tipo === 'Readecuacion') {
        if ($tipo === 'Refinanciamiento' || $tipo === 'Readecuacion') {
            // Buscar préstamos anteriores activos para deducir saldo (Suma de todos)
            $stmtPrevCalc = $db->prepare("SELECT 
                                        SUM(p.monto_capital - (
                                            SELECT IFNULL(SUM(monto_pagado * (capital_cuota/monto_cuota)), 0) 
                                            FROM cuotas WHERE prestamo_id = p.id AND estado IN ('pagada', 'parcial') AND monto_cuota > 0
                                        )) as saldo_pendiente_total
                                      FROM prestamos p 
                                      WHERE id_cliente = ? 
                                      AND estado IN ('Activo', 'Vencido')");
            $stmtPrevCalc->execute([$clienteId]);
            $prevCalc = $stmtPrevCalc->fetch(PDO::FETCH_ASSOC);

            if ($prevCalc && $prevCalc['saldo_pendiente_total'] !== null) {
                $saldoAnterior = max(0, floatval($prevCalc['saldo_pendiente_total']));
                $netoEntregar = max(0, $monto - $saldoAnterior);

                if ($saldoAnterior > 0) {
                    $observaciones .= " [Refinanciamiento: Se deduce saldo anterior total de L " . number_format($saldoAnterior, 2) . "]";
                }
            }
        }
    }
    // -----------------------------------------------------

    $sql = "INSERT INTO prestamos (
        id_cliente, asesor_creditos_id, monto_capital, modalidad, plazo_meses, 
        tasa_total, tasa_interes, tasa_gastos, tasa_comision,
        valor_cuota, total_a_pagar, neto_entregar, estado, fecha_solicitud, observaciones, tipo_prestamo
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?,
        ?, ?, ?, ?, NOW(), ?, ?
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
        $netoEntregar,  // Calculated Net
        $estadoInicial,
        $observaciones,
        $tipo
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