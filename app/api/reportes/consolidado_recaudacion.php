<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión (Administrador)
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    // TODO: Validar rol de administrador
    // AuthMiddleware::requireAdmin();

    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $agenciaId = $_GET['agencia_id'] ?? 'todas'; // 'todas' o ID numérico

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Determinar filtro de agencia
    $filtroAgenciaSql = "";
    $paramsResumen = [$fecha];
    $paramsTransacciones = [$fecha];

    $nombreAgencia = "CONSOLIDADO (TODAS LAS AGENCIAS)";

    if ($agenciaId !== 'todas' && is_numeric($agenciaId)) {
        $filtroAgenciaSql = "AND c.id_agencia = ?";
        $paramsResumen[] = $agenciaId;
        $paramsTransacciones[] = $agenciaId;

        // Obtener nombre de la agencia
        $stmt = $db->prepare("SELECT nombre_agencia FROM agencias WHERE id_agencia = ?");
        $stmt->execute([$agenciaId]);
        $nombreAgencia = $stmt->fetchColumn() ?: "Agencia Desconocida";
    }

    // 1. TOTAL COBRADO HOY
    $sqlResumen = "SELECT 
                   IFNULL(SUM(cu.monto_pagado), 0) as total_cobrado
                   FROM cuotas cu
                   INNER JOIN prestamos p ON cu.prestamo_id = p.id
                   INNER JOIN clientes c ON p.id_cliente = c.id
                   WHERE DATE(cu.fecha_pago_real) = ? 
                   $filtroAgenciaSql
                   AND cu.monto_pagado > 0";

    $stmtResumen = $db->prepare($sqlResumen);
    $stmtResumen->execute($paramsResumen);
    $totalCobrado = floatval($stmtResumen->fetchColumn());

    // CÁLCULO DE DESGLOSE (Mejorado para evitar errores de redondeo)
    // Sumar directamente los componentes proporcionales de cada pago
    $sqlDesglose = "SELECT 
                    IFNULL(SUM(
                        cu.monto_pagado * (cu.capital_cuota / NULLIF(cu.monto_cuota, 0))
                    ), 0) as capital_total,
                    IFNULL(SUM(
                        cu.monto_pagado * (cu.interes_cuota / NULLIF(cu.monto_cuota, 0))
                    ), 0) as interes_total,
                    IFNULL(SUM(
                        cu.monto_pagado * (cu.gastos_cuota / NULLIF(cu.monto_cuota, 0))
                    ), 0) as gastos_total,
                    IFNULL(SUM(
                        cu.monto_pagado * (cu.comision_cuota / NULLIF(cu.monto_cuota, 0))
                    ), 0) as comision_total
                    FROM cuotas cu
                    INNER JOIN prestamos p ON cu.prestamo_id = p.id
                    INNER JOIN clientes c ON p.id_cliente = c.id
                    WHERE DATE(cu.fecha_pago_real) = ? 
                    $filtroAgenciaSql
                    AND cu.monto_pagado > 0
                    AND cu.monto_cuota > 0";

    $stmtDesglose = $db->prepare($sqlDesglose);
    $stmtDesglose->execute($paramsResumen);
    $desglose = $stmtDesglose->fetch(PDO::FETCH_ASSOC);

    $capitalRecaudado = round(floatval($desglose['capital_total']), 2);
    $interesRecaudado = round(floatval($desglose['interes_total']), 2);
    $gastosRecaudado = round(floatval($desglose['gastos_total']), 2);
    $comisionRecaudada = round(floatval($desglose['comision_total']), 2);

    // Total interés completo (4% + 4% + 3% = 11%)
    $totalInteresCompleto = $interesRecaudado + $gastosRecaudado + $comisionRecaudada;

    // 2. LISTADO DE TRANSACCIONES DETALLADO (Agrupado por préstamo y fecha)
    $sqlTransacciones = "SELECT 
                         p.id as prestamo_id,
                         c.nombre_completo, 
                         c.id as cliente_id,
                         cu.numero_cuota, 
                         cu.monto_pagado, 
                         cu.fecha_pago_real,
                         cu.monto_cuota,
                         cu.capital_cuota,
                         cu.interes_cuota,
                         cu.gastos_cuota,
                         cu.comision_cuota,
                         ag.nombre_agencia
                         FROM cuotas cu
                         INNER JOIN prestamos p ON cu.prestamo_id = p.id
                         INNER JOIN clientes c ON p.id_cliente = c.id
                         LEFT JOIN agencias ag ON c.id_agencia = ag.id_agencia
                         WHERE DATE(cu.fecha_pago_real) = ? 
                         $filtroAgenciaSql
                         AND cu.monto_pagado > 0
                         ORDER BY cu.fecha_pago_real DESC, c.nombre_completo ASC, cu.numero_cuota ASC";

    $stmtTrans = $db->prepare($sqlTransacciones);
    $stmtTrans->execute($paramsTransacciones);
    $transaccionesRaw = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // AGRUPAR TRANSACCIONES por préstamo y fecha_pago_real
    $transaccionesAgrupadas = [];
    foreach ($transaccionesRaw as $t) {
        $key = $t['prestamo_id'] . '_' . $t['fecha_pago_real'];

        if (!isset($transaccionesAgrupadas[$key])) {
            $transaccionesAgrupadas[$key] = [
                'prestamo_id' => $t['prestamo_id'],
                'nombre_completo' => $t['nombre_completo'],
                'agencia' => $t['nombre_agencia'] ?? 'N/A',
                'fecha_pago_real' => $t['fecha_pago_real'],
                'monto_pagado_total' => 0,
                'cuotas' => []
            ];
        }

        $transaccionesAgrupadas[$key]['monto_pagado_total'] += floatval($t['monto_pagado']);
        $transaccionesAgrupadas[$key]['cuotas'][] = intval($t['numero_cuota']);
    }

    // Formatear salida
    $transacciones = [];
    foreach ($transaccionesAgrupadas as $trans) {
        sort($trans['cuotas']);
        $cuotasStr = implode(', ', $trans['cuotas']);

        $transacciones[] = [
            'prestamo_id' => $trans['prestamo_id'],
            'nombre_completo' => $trans['nombre_completo'],
            'agencia' => $trans['agencia'],
            'cuotas' => $cuotasStr,
            'monto_pagado' => round($trans['monto_pagado_total'], 2),
            'fecha_pago' => $trans['fecha_pago_real']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fecha,
            'agencia' => $nombreAgencia,
            'agencia_id' => $agenciaId,
            'total_cobrado' => round($totalCobrado, 2),
            'capital' => $capitalRecaudado,
            'interes' => $interesRecaudado,
            'gastos' => $gastosRecaudado,
            'comision' => $comisionRecaudada,
            'total_interes_completo' => round($totalInteresCompleto, 2),
            'transacciones' => $transacciones,
            'total_transacciones' => count($transacciones)
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => 'Error en consolidado_recaudacion.php'
    ], JSON_PRETTY_PRINT);
}
?>