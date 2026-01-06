<?php
/**
 * Reporte de Cobros
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros de filtro
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01'); // Primer día del mes
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
    $cobradorId = isset($_GET['cobrador_id']) ? intval($_GET['cobrador_id']) : null;
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
    $estado = isset($_GET['estado']) ? $_GET['estado'] : null;
    
    // Construir query
    $where = ["DATE(p.fecha_pago) BETWEEN :fecha_desde AND :fecha_hasta"];
    $params = [
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta
    ];
    
    if ($cobradorId) {
        $where[] = "p.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $cobradorId;
    }
    
    if ($clienteId) {
        $where[] = "c.id = :cliente_id";
        $params['cliente_id'] = $clienteId;
    }
    
    if ($estado) {
        $where[] = "p.estado = :estado";
        $params['estado'] = $estado;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Query principal
    $sql = "
        SELECT 
            p.id,
            p.fecha_pago,
            p.monto_pagado,
            p.monto_mora,
            p.metodo_pago,
            p.estado,
            p.comprobante_url,
            c.id as cliente_id,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            pr.numero_prestamo,
            cu.numero_cuota,
            cob.nombre_completo as cobrador_nombre
        FROM pagos p
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        INNER JOIN prestamos pr ON cu.prestamo_id = pr.id
        INNER JOIN clientes c ON pr.cliente_id = c.id
        LEFT JOIN usuarios cob ON p.cobrador_id = cob.id
        WHERE $whereClause
        ORDER BY p.fecha_pago DESC, p.id DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cobros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas
    $statsSql = "
        SELECT 
            COUNT(*) as total_cobros,
            COALESCE(SUM(p.monto_pagado), 0) as total_monto,
            COALESCE(SUM(p.monto_mora), 0) as total_mora,
            COALESCE(SUM(CASE WHEN p.estado = 'confirmado' THEN p.monto_pagado ELSE 0 END), 0) as total_confirmado,
            COALESCE(SUM(CASE WHEN p.estado = 'pendiente' THEN p.monto_pagado ELSE 0 END), 0) as total_pendiente
        FROM pagos p
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        INNER JOIN prestamos pr ON cu.prestamo_id = pr.id
        INNER JOIN clientes c ON pr.cliente_id = c.id
        WHERE $whereClause
    ";
    
    $statsStmt = $db->prepare($statsSql);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch();
    
    Response::success([
        'cobros' => $cobros,
        'estadisticas' => [
            'total_cobros' => (int)$stats['total_cobros'],
            'total_monto' => (float)$stats['total_monto'],
            'total_mora' => (float)$stats['total_mora'],
            'total_confirmado' => (float)$stats['total_confirmado'],
            'total_pendiente' => (float)$stats['total_pendiente'],
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reporte de cobros: " . $e->getMessage());
    Response::serverError('Error al generar reporte de cobros');
}

