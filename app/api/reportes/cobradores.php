<?php
/**
 * Reporte de Desempeño de Cobradores
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
    
    // Query principal
    $sql = "
        SELECT 
            cob.id,
            cob.nombre_completo as cobrador_nombre,
            cob.usuario,
            cob.email,
            cob.estado,
            COUNT(DISTINCT c.id) as total_clientes,
            COUNT(DISTINCT CASE WHEN c.estado = 'activo' THEN c.id END) as clientes_activos,
            COUNT(DISTINCT pr.id) as total_prestamos,
            COUNT(DISTINCT CASE WHEN pr.estado = 'activo' THEN pr.id END) as prestamos_activos,
            COUNT(DISTINCT p.id) as total_cobros,
            COUNT(DISTINCT CASE WHEN DATE(p.fecha_pago) BETWEEN :fecha_desde AND :fecha_hasta THEN p.id END) as cobros_periodo,
            COALESCE(SUM(CASE WHEN DATE(p.fecha_pago) BETWEEN :fecha_desde2 AND :fecha_hasta2 AND p.estado = 'confirmado' THEN p.monto_pagado ELSE 0 END), 0) as monto_cobrado_periodo,
            COALESCE(SUM(CASE WHEN DATE(p.fecha_pago) BETWEEN :fecha_desde3 AND :fecha_hasta3 AND p.estado = 'confirmado' THEN p.monto_mora ELSE 0 END), 0) as mora_cobrada_periodo,
            COALESCE(SUM(CASE WHEN DATE(p.fecha_pago) = CURDATE() AND p.estado = 'confirmado' THEN p.monto_pagado ELSE 0 END), 0) as monto_cobrado_hoy,
            COUNT(DISTINCT CASE WHEN DATE(p.fecha_pago) = CURDATE() AND p.estado = 'confirmado' THEN p.id END) as cobros_hoy
        FROM usuarios cob
        LEFT JOIN clientes c ON c.cobrador_id = cob.id
        LEFT JOIN prestamos pr ON pr.cliente_id = c.id
        LEFT JOIN pagos p ON p.cobrador_id = cob.id
        WHERE cob.rol = 'cobrador'
        GROUP BY cob.id, cob.nombre_completo, cob.usuario, cob.email, cob.estado
        ORDER BY monto_cobrado_periodo DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'fecha_desde' => $fechaDesde,
        'fecha_hasta' => $fechaHasta,
        'fecha_desde2' => $fechaDesde,
        'fecha_hasta2' => $fechaHasta,
        'fecha_desde3' => $fechaDesde,
        'fecha_hasta3' => $fechaHasta
    ]);
    $cobradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular promedios y eficiencia
    $totalMonto = array_sum(array_column($cobradores, 'monto_cobrado_periodo'));
    $totalCobros = array_sum(array_column($cobradores, 'cobros_periodo'));
    
    foreach ($cobradores as &$cobrador) {
        $cobrador['promedio_por_cobro'] = $cobrador['cobros_periodo'] > 0 
            ? $cobrador['monto_cobrado_periodo'] / $cobrador['cobros_periodo'] 
            : 0;
        $cobrador['porcentaje_total'] = $totalMonto > 0 
            ? ($cobrador['monto_cobrado_periodo'] / $totalMonto * 100) 
            : 0;
    }
    unset($cobrador);
    
    Response::success([
        'cobradores' => $cobradores,
        'estadisticas' => [
            'total_cobradores' => count($cobradores),
            'total_monto_periodo' => (float)$totalMonto,
            'total_cobros_periodo' => (int)$totalCobros,
            'promedio_por_cobrador' => count($cobradores) > 0 ? $totalMonto / count($cobradores) : 0,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en reporte de cobradores: " . $e->getMessage());
    Response::serverError('Error al generar reporte de cobradores');
}

