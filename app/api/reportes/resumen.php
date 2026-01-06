<?php
/**
 * API: Reporte resumen por país, agencia y cobrador
 * GET /app/api/reportes/resumen.php?pais=&agencia_id=&cobrador_id=&fecha_desde=&fecha_hasta=
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { Response::error('Método no permitido', 405); }

try {
    $user = AuthMiddleware::requireAdmin(); // Reportes solo admin
    $db = getDB();

    $pais = isset($_GET['pais']) ? trim($_GET['pais']) : '';
    $agenciaId = isset($_GET['agencia_id']) ? intval($_GET['agencia_id']) : null;
    $cobradorId = isset($_GET['cobrador_id']) ? intval($_GET['cobrador_id']) : null;
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

    $where = [];$params = [];
    if (!empty($pais)) { $where[] = 'c.pais = :pais'; $params['pais'] = $pais; }
    if ($agenciaId) { $where[] = 'c.agencia_id = :agencia_id'; $params['agencia_id'] = $agenciaId; }
    if ($cobradorId) { $where[] = 'c.cobrador_id = :cobrador_id'; $params['cobrador_id'] = $cobradorId; }

    $wherePrestamos = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Rango de fechas para pagos
    $wherePagos = [];$paramsPagos = [];
    if (!empty($pais)) { $wherePagos[] = 'c.pais = :pais'; $paramsPagos['pais'] = $pais; }
    if ($agenciaId) { $wherePagos[] = 'c.agencia_id = :agencia_id'; $paramsPagos['agencia_id'] = $agenciaId; }
    if ($cobradorId) { $wherePagos[] = 'c.cobrador_id = :cobrador_id'; $paramsPagos['cobrador_id'] = $cobradorId; }
    if ($fechaDesde) { $wherePagos[] = 'pg.fecha_pago >= :fecha_desde'; $paramsPagos['fecha_desde'] = $fechaDesde; }
    if ($fechaHasta) { $wherePagos[] = 'pg.fecha_pago <= :fecha_hasta'; $paramsPagos['fecha_hasta'] = $fechaHasta; }
    $wherePagosClause = !empty($wherePagos) ? 'WHERE ' . implode(' AND ', $wherePagos) : '';

    // Resumen de préstamos
    $sqlPrest = "SELECT 
                    COUNT(DISTINCT p.id) AS prestamos_total,
                    SUM(p.monto_prestado) AS monto_prestado_total,
                    SUM(p.monto_total) AS monto_total,
                    SUM(CASE WHEN p.estado = 'activo' THEN 1 ELSE 0 END) AS prestamos_activos,
                    SUM(CASE WHEN p.estado = 'completado' THEN 1 ELSE 0 END) AS prestamos_completados,
                    SUM(CASE WHEN p.estado = 'en_mora' THEN 1 ELSE 0 END) AS prestamos_en_mora
                 FROM prestamos p 
                 INNER JOIN clientes c ON p.cliente_id = c.id
                 {$wherePrestamos}";
    $stmt = $db->prepare($sqlPrest);
    $stmt->execute($params);
    $prestamos = $stmt->fetch();

    // Resumen de pagos
    $sqlPagos = "SELECT 
                    COUNT(pg.id) AS pagos_count,
                    SUM(pg.monto_pagado) AS pagos_total,
                    SUM(pg.monto_mora) AS mora_total
                 FROM pagos pg
                 INNER JOIN prestamos p ON pg.prestamo_id = p.id
                 INNER JOIN clientes c ON pg.cliente_id = c.id
                 {$wherePagosClause}";
    $stmt = $db->prepare($sqlPagos);
    $stmt->execute($paramsPagos);
    $pagos = $stmt->fetch();

    Response::success([
        'prestamos' => [
            'total' => (int)($prestamos['prestamos_total'] ?? 0),
            'monto_prestado_total' => round(floatval($prestamos['monto_prestado_total'] ?? 0), 2),
            'monto_total' => round(floatval($prestamos['monto_total'] ?? 0), 2),
            'activos' => (int)($prestamos['prestamos_activos'] ?? 0),
            'completados' => (int)($prestamos['prestamos_completados'] ?? 0),
            'en_mora' => (int)($prestamos['prestamos_en_mora'] ?? 0)
        ],
        'pagos' => [
            'count' => (int)($pagos['pagos_count'] ?? 0),
            'total' => round(floatval($pagos['pagos_total'] ?? 0), 2),
            'mora_total' => round(floatval($pagos['monto_mora'] ?? 0), 2)
        ]
    ], 'Reporte generado');

} catch (Exception $e) {
    error_log('Error en reportes/resumen.php: ' . $e->getMessage());
    Response::serverError('Error al generar reporte');
}
