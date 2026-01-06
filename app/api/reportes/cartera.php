<?php
/**
 * Reporte de Cartera
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros
    $cobradorId = isset($_GET['cobrador_id']) ? intval($_GET['cobrador_id']) : null;
    $estado = isset($_GET['estado']) ? $_GET['estado'] : 'activo';
    
    // Construir query
    $where = ["pr.estado = :estado"];
    $params = ['estado' => $estado];
    
    if ($cobradorId) {
        $where[] = "c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $cobradorId;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Query principal
    $sql = "
        SELECT 
            pr.id,
            pr.numero_prestamo,
            pr.monto_prestado,
            pr.monto_total,
            pr.tasa_interes,
            pr.periodo_meses,
            pr.fecha_desembolso,
            pr.estado,
            c.id as cliente_id,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            cob.nombre_completo as cobrador_nombre,
            COUNT(DISTINCT cu.id) as total_cuotas,
            COUNT(DISTINCT CASE WHEN cu.estado = 'pagada' THEN cu.id END) as cuotas_pagadas,
            COUNT(DISTINCT CASE WHEN cu.estado != 'pagada' THEN cu.id END) as cuotas_pendientes,
            COALESCE(SUM(CASE WHEN cu.estado = 'pagada' THEN cu.monto_pagado ELSE 0 END), 0) as monto_pagado,
            (pr.monto_total - COALESCE(SUM(CASE WHEN cu.estado = 'pagada' THEN cu.monto_pagado ELSE 0 END), 0)) as saldo_pendiente
        FROM prestamos pr
        INNER JOIN clientes c ON pr.cliente_id = c.id
        LEFT JOIN usuarios cob ON c.cobrador_id = cob.id
        LEFT JOIN cuotas cu ON cu.prestamo_id = pr.id
        WHERE $whereClause
        GROUP BY pr.id, pr.numero_prestamo, pr.monto_prestado, pr.monto_total, pr.tasa_interes, 
                 pr.periodo_meses, pr.fecha_desembolso, pr.estado, c.id, c.nombre_completo, 
                 c.codigo_cliente, cob.nombre_completo
        ORDER BY pr.fecha_desembolso DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas
    $totalPrestamos = count($prestamos);
    $totalCartera = array_sum(array_column($prestamos, 'saldo_pendiente'));
    $totalDesembolsado = array_sum(array_column($prestamos, 'monto_prestado'));
    $totalPagado = array_sum(array_column($prestamos, 'monto_pagado'));
    $totalCuotasPendientes = array_sum(array_column($prestamos, 'cuotas_pendientes'));
    
    // Agrupar por cobrador
    $carteraPorCobrador = [];
    foreach ($prestamos as $prestamo) {
        $cobrador = $prestamo['cobrador_nombre'] ?: 'Sin asignar';
        if (!isset($carteraPorCobrador[$cobrador])) {
            $carteraPorCobrador[$cobrador] = [
                'cobrador' => $cobrador,
                'total_prestamos' => 0,
                'total_cartera' => 0,
                'total_desembolsado' => 0
            ];
        }
        $carteraPorCobrador[$cobrador]['total_prestamos']++;
        $carteraPorCobrador[$cobrador]['total_cartera'] += $prestamo['saldo_pendiente'];
        $carteraPorCobrador[$cobrador]['total_desembolsado'] += $prestamo['monto_prestado'];
    }
    $carteraPorCobrador = array_values($carteraPorCobrador);
    
    Response::success([
        'prestamos' => $prestamos,
        'cartera_por_cobrador' => $carteraPorCobrador,
        'estadisticas' => [
            'total_prestamos' => $totalPrestamos,
            'total_cartera' => (float)$totalCartera,
            'total_desembolsado' => (float)$totalDesembolsado,
            'total_pagado' => (float)$totalPagado,
            'total_cuotas_pendientes' => (int)$totalCuotasPendientes,
            'porcentaje_pagado' => $totalDesembolsado > 0 ? ($totalPagado / $totalDesembolsado * 100) : 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reporte de cartera: " . $e->getMessage());
    Response::serverError('Error al generar reporte de cartera');
}

