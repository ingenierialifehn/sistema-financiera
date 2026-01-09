<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    // SEGURIDAD: Validar sesión y obtener id_agencia
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión no válida. Por favor, inicie sesión nuevamente.');
    }

    $idAgencia = $_SESSION['id_agencia'] ?? null;

    if (!$idAgencia || empty($idAgencia)) {
        throw new Exception('No se pudo determinar la agencia del usuario. Verifique su perfil.');
    }

    $db = getDB();
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $fechaHoy = date('Y-m-d');

    // Verificar que la agencia existe
    $sqlVerifAgencia = "SELECT nombre_agencia FROM agencias WHERE id_agencia = ?";
    $stmtVerif = $db->prepare($sqlVerifAgencia);
    $stmtVerif->execute([$idAgencia]);
    $nombreAgencia = $stmtVerif->fetchColumn();

    if (!$nombreAgencia) {
        throw new Exception('La agencia asignada no existe en el sistema');
    }

    // TOTAL COBRADO HOY - Mejorado para incluir solo pagos válidos
    $sqlTotal = "SELECT IFNULL(SUM(cu.monto_pagado), 0) as total_cobrado
                 FROM cuotas cu
                 INNER JOIN prestamos p ON cu.prestamo_id = p.id
                 INNER JOIN clientes c ON p.id_cliente = c.id
                 WHERE DATE(cu.fecha_pago_real) = ?
                 AND c.id_agencia = ?
                 AND cu.estado = 'pagada'
                 AND cu.monto_pagado > 0
                 AND cu.fecha_pago_real IS NOT NULL";

    $stmtTotal = $db->prepare($sqlTotal);
    $stmtTotal->execute([$fechaHoy, $idAgencia]);
    $totalCobrado = floatval($stmtTotal->fetchColumn());

    // DESGLOSE CONTABLE MEJORADO
    // Ahora calculamos correctamente el desglose basado en los datos reales de las cuotas
    $sqlDesglose = "SELECT 
                    cu.id,
                    cu.capital_cuota,
                    cu.interes_cuota,
                    cu.gastos_cuota,
                    cu.comision_cuota,
                    cu.monto_pagado,
                    cu.monto_cuota
                    FROM cuotas cu
                    INNER JOIN prestamos p ON cu.prestamo_id = p.id
                    INNER JOIN clientes c ON p.id_cliente = c.id
                    WHERE DATE(cu.fecha_pago_real) = ?
                    AND c.id_agencia = ?
                    AND cu.estado = 'pagada'
                    AND cu.monto_pagado > 0";

    $stmtDesglose = $db->prepare($sqlDesglose);
    $stmtDesglose->execute([$fechaHoy, $idAgencia]);
    $pagos = $stmtDesglose->fetchAll(PDO::FETCH_ASSOC);

    $totalCapital = 0;
    $totalInteres = 0;
    $totalGastos = 0;
    $totalComision = 0;

    foreach ($pagos as $pago) {
        $montoPagado = floatval($pago['monto_pagado']);
        $montoCuota = floatval($pago['monto_cuota'] ?? 0);

        // Si la cuota tiene desglose válido, usarlo
        if (!empty($pago['capital_cuota']) && floatval($pago['capital_cuota']) > 0) {
            // Usar el desglose existente
            $capitalCuota = floatval($pago['capital_cuota']);
            $interesCuota = floatval($pago['interes_cuota'] ?? 0);
            $gastosCuota = floatval($pago['gastos_cuota'] ?? 0);
            $comisionCuota = floatval($pago['comision_cuota'] ?? 0);

            // Si es pago parcial, calcular proporción
            if ($montoPagado < $montoCuota && $montoCuota > 0) {
                $proporcion = $montoPagado / $montoCuota;
                $totalCapital += $capitalCuota * $proporcion;
                $totalInteres += $interesCuota * $proporcion;
                $totalGastos += $gastosCuota * $proporcion;
                $totalComision += $comisionCuota * $proporcion;
            } else {
                // Pago completo
                $totalCapital += $capitalCuota;
                $totalInteres += $interesCuota;
                $totalGastos += $gastosCuota;
                $totalComision += $comisionCuota;
            }
        } else {
            // Si no tiene desglose, calcularlo con la fórmula estándar
            // Total = Capital * 1.11 (donde 11% = 4% interés + 4% gastos + 3% comisión)
            $capital = $montoPagado / 1.11;
            $totalCapital += $capital;
            $totalInteres += $capital * 0.04;
            $totalGastos += $capital * 0.04;
            $totalComision += $capital * 0.03;
        }
    }

    // LISTA DE TRANSACCIONES DEL DÍA - Mejorada con más información
    $sqlTransacciones = "SELECT 
                         c.nombre_completo,
                         c.numero_documento,
                         cu.numero_cuota,
                         cu.monto_pagado,
                         cu.monto_cuota,
                         cu.fecha_pago_real as fecha_pago,
                         cu.capital_cuota,
                         cu.interes_cuota,
                         cu.gastos_cuota,
                         cu.comision_cuota,
                         p.id as prestamo_id,
                         p.modalidad,
                         COALESCE(col.nombre_completo, u.username, 'Sistema') as cobrador
                         FROM cuotas cu
                         INNER JOIN prestamos p ON cu.prestamo_id = p.id
                         INNER JOIN clientes c ON p.id_cliente = c.id
                         LEFT JOIN usuarios u ON cu.usuario_cobro_id = u.id_usuario
                         LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
                         WHERE DATE(cu.fecha_pago_real) = ?
                         AND c.id_agencia = ?
                         AND cu.estado = 'pagada'
                         AND cu.monto_pagado > 0
                         ORDER BY cu.fecha_pago_real DESC";

    $stmtTrans = $db->prepare($sqlTransacciones);
    $stmtTrans->execute([$fechaHoy, $idAgencia]);
    $transacciones = $stmtTrans->fetchAll(PDO::FETCH_ASSOC);

    // Calcular desglose para cada transacción si no existe
    foreach ($transacciones as &$trans) {
        $montoPagado = floatval($trans['monto_pagado']);
        $montoCuota = floatval($trans['monto_cuota'] ?? 0);

        if (empty($trans['capital_cuota']) || floatval($trans['capital_cuota']) <= 0) {
            // Calcular desglose
            $capital = $montoPagado / 1.11;
            $trans['capital_cuota'] = $capital;
            $trans['interes_cuota'] = $capital * 0.04;
            $trans['gastos_cuota'] = $capital * 0.04;
            $trans['comision_cuota'] = $capital * 0.03;
        } else if ($montoPagado < $montoCuota && $montoCuota > 0) {
            // Ajustar por pago parcial
            $proporcion = $montoPagado / $montoCuota;
            $trans['capital_cuota'] = floatval($trans['capital_cuota']) * $proporcion;
            $trans['interes_cuota'] = floatval($trans['interes_cuota'] ?? 0) * $proporcion;
            $trans['gastos_cuota'] = floatval($trans['gastos_cuota'] ?? 0) * $proporcion;
            $trans['comision_cuota'] = floatval($trans['comision_cuota'] ?? 0) * $proporcion;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'fecha' => $fechaHoy,
            'agencia' => $nombreAgencia,
            'id_agencia' => $idAgencia,
            'total_cobrado' => round($totalCobrado, 2),
            'desglose' => [
                'capital' => round($totalCapital, 2),
                'interes' => round($totalInteres, 2),
                'gastos' => round($totalGastos, 2),
                'comision' => round($totalComision, 2)
            ],
            'transacciones' => $transacciones,
            'cantidad_transacciones' => count($transacciones)
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => 'Error en recaudacion_diaria.php'
    ], JSON_PRETTY_PRINT);
}
?>