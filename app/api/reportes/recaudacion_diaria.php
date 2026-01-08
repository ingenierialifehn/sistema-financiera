<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Obtener id_agencia de la sesión
    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia) {
        throw new Exception('No se pudo determinar la agencia del usuario');
    }

    $db = getDB();
    $fechaHoy = date('Y-m-d');

    // TOTAL COBRADO HOY
    $sqlTotal = "SELECT IFNULL(SUM(monto_pagado), 0) as total_cobrado
                 FROM cuotas cu
                 JOIN prestamos p ON cu.prestamo_id = p.id
                 JOIN clientes c ON p.id_cliente = c.id
                 WHERE DATE(cu.fecha_pago_real) = ?
                 AND c.id_agencia = ?
                 AND cu.estado = 'pagada'";

    $stmtTotal = $db->prepare($sqlTotal);
    $stmtTotal->execute([$fechaHoy, $idAgencia]);
    $totalCobrado = floatval($stmtTotal->fetchColumn());

    // DESGLOSE CONTABLE (Capital + 11%)
    // Si las cuotas tienen el desglose, lo usamos. Si no, calculamos:
    // Capital = monto_pagado / 1.11
    // Interés 4% = capital * 0.04
    // Gastos 4% = capital * 0.04
    // Comisión 3% = capital * 0.03

    $sqlDesglose = "SELECT 
                    cu.capital_cuota,
                    cu.interes_cuota,
                    cu.gastos_cuota,
                    cu.comision_cuota,
                    cu.monto_pagado
                    FROM cuotas cu
                    JOIN prestamos p ON cu.prestamo_id = p.id
                    JOIN clientes c ON p.id_cliente = c.id
                    WHERE DATE(cu.fecha_pago_real) = ?
                    AND c.id_agencia = ?
                    AND cu.estado = 'pagada'";

    $stmtDesglose = $db->prepare($sqlDesglose);
    $stmtDesglose->execute([$fechaHoy, $idAgencia]);
    $pagos = $stmtDesglose->fetchAll(PDO::FETCH_ASSOC);

    $totalCapital = 0;
    $totalInteres = 0;
    $totalGastos = 0;
    $totalComision = 0;

    foreach ($pagos as $pago) {
        $montoPagado = floatval($pago['monto_pagado']);

        // Si la cuota tiene desglose, usarlo
        if (!empty($pago['capital_cuota']) && $pago['capital_cuota'] > 0) {
            $totalCapital += floatval($pago['capital_cuota']);
            $totalInteres += floatval($pago['interes_cuota'] ?? 0);
            $totalGastos += floatval($pago['gastos_cuota'] ?? 0);
            $totalComision += floatval($pago['comision_cuota'] ?? 0);
        } else {
            // Si no tiene desglose, calcularlo
            $capital = $montoPagado / 1.11;
            $totalCapital += $capital;
            $totalInteres += $capital * 0.04;
            $totalGastos += $capital * 0.04;
            $totalComision += $capital * 0.03;
        }
    }

    // LISTA DE TRANSACCIONES DEL DÍA
    $sqlTransacciones = "SELECT 
                         c.nombre_completo,
                         cu.numero_cuota,
                         cu.monto_pagado,
                         cu.fecha_pago_real as fecha_pago,
                         cu.capital_cuota,
                         cu.interes_cuota,
                         cu.gastos_cuota,
                         cu.comision_cuota,
                         p.id as prestamo_id
                         FROM cuotas cu
                         JOIN prestamos p ON cu.prestamo_id = p.id
                         JOIN clientes c ON p.id_cliente = c.id
                         WHERE DATE(cu.fecha_pago_real) = ?
                         AND c.id_agencia = ?
                         AND cu.estado = 'pagada'
                         ORDER BY cu.fecha_pago_real DESC";

    $stmtTrans = $db->prepare($sqlTransacciones);
    $stmtTrans->execute([$fechaHoy, $idAgencia]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // Obtener nombre de la agencia
    $sqlAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtAgencia = $db->prepare($sqlAgencia);
    $stmtAgencia->execute([$idAgencia]);
    $nombreAgencia = $stmtAgencia->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'agencia' => $nombreAgencia,
            'total_cobrado' => $totalCobrado,
            'desglose' => [
                'capital' => $totalCapital,
                'interes' => $totalInteres,
                'gastos' => $totalGastos,
                'comision' => $totalComision
            ],
            'transacciones' => $transacciones
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>