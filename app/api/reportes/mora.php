<?php
/**
 * Reporte de Mora
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros de filtro
    $cobradorId = isset($_GET['cobrador_id']) ? intval($_GET['cobrador_id']) : null;
    $diasMoraMin = isset($_GET['dias_mora_min']) ? intval($_GET['dias_mora_min']) : 1;
    
    // Construir query
    $where = [
        "cu.estado != 'pagada'",
        "cu.fecha_vencimiento < CURDATE()"
    ];
    $params = [];
    
    if ($cobradorId) {
        $where[] = "c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $cobradorId;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Obtener cuotas en mora
    $sql = "
        SELECT 
            cu.id,
            cu.numero_cuota,
            cu.fecha_vencimiento,
            cu.monto_cuota,
            cu.monto_pagado,
            (cu.monto_cuota - COALESCE(cu.monto_pagado, 0)) as saldo_pendiente,
            DATEDIFF(CURDATE(), cu.fecha_vencimiento) as dias_mora,
            pr.id as prestamo_id,
            pr.numero_prestamo,
            pr.monto_prestado,
            c.id as cliente_id,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            c.telefono,
            cob.nombre_completo as cobrador_nombre
        FROM cuotas cu
        INNER JOIN prestamos pr ON cu.prestamo_id = pr.id
        INNER JOIN clientes c ON pr.cliente_id = c.id
        LEFT JOIN usuarios cob ON c.cobrador_id = cob.id
        WHERE $whereClause
        HAVING dias_mora >= :dias_mora_min
        ORDER BY dias_mora DESC, cu.fecha_vencimiento ASC
    ";
    
    $params['dias_mora_min'] = $diasMoraMin;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cuotasMora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular mora para cada cuota
    foreach ($cuotasMora as &$cuota) {
        $mora = calculateMora($cuota['fecha_vencimiento'], $cuota['monto_cuota']);
        $cuota['monto_mora'] = $mora['monto'];
        $cuota['dias_mora'] = $mora['dias'];
    }
    unset($cuota);
    
    // Estadísticas
    $totalMora = array_sum(array_column($cuotasMora, 'monto_mora'));
    $totalSaldo = array_sum(array_column($cuotasMora, 'saldo_pendiente'));
    $totalCuotas = count($cuotasMora);
    
    // Agrupar por cliente
    $clientesMora = [];
    foreach ($cuotasMora as $cuota) {
        $clienteId = $cuota['cliente_id'];
        if (!isset($clientesMora[$clienteId])) {
            $clientesMora[$clienteId] = [
                'cliente_id' => $clienteId,
                'cliente_nombre' => $cuota['cliente_nombre'],
                'codigo_cliente' => $cuota['codigo_cliente'],
                'telefono' => $cuota['telefono'],
                'cobrador_nombre' => $cuota['cobrador_nombre'],
                'total_cuotas' => 0,
                'total_saldo' => 0,
                'total_mora' => 0,
                'dias_mora_max' => 0
            ];
        }
        $clientesMora[$clienteId]['total_cuotas']++;
        $clientesMora[$clienteId]['total_saldo'] += $cuota['saldo_pendiente'];
        $clientesMora[$clienteId]['total_mora'] += $cuota['monto_mora'];
        $clientesMora[$clienteId]['dias_mora_max'] = max($clientesMora[$clienteId]['dias_mora_max'], $cuota['dias_mora']);
    }
    $clientesMora = array_values($clientesMora);
    
    Response::success([
        'cuotas_mora' => $cuotasMora,
        'clientes_mora' => $clientesMora,
        'estadisticas' => [
            'total_cuotas' => $totalCuotas,
            'total_saldo' => (float)$totalSaldo,
            'total_mora' => (float)$totalMora,
            'total_clientes' => count($clientesMora)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reporte de mora: " . $e->getMessage());
    Response::serverError('Error al generar reporte de mora');
}

