<?php
/**
 * Obtener cobrador por ID
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error('ID de cobrador requerido', 400);
}

$id = intval($_GET['id']);

try {
    // Obtener cobrador
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.usuario,
            u.nombre_completo,
            u.email,
            u.rol,
            u.estado,
            u.created_at,
            u.updated_at
        FROM usuarios u
        WHERE u.id = :id AND u.rol = 'cobrador'
    ");
    $stmt->execute(['id' => $id]);
    $cobrador = $stmt->fetch();
    
    if (!$cobrador) {
        Response::notFound('Cobrador no encontrado');
    }
    
    // Obtener estadísticas
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_clientes,
            COUNT(DISTINCT CASE WHEN c.estado = 'activo' THEN c.id END) as clientes_activos,
            COUNT(DISTINCT p.id) as total_prestamos,
            COALESCE(SUM(CASE WHEN pag.estado = 'confirmado' AND DATE(pag.fecha_pago) = CURDATE() THEN pag.monto_pagado ELSE 0 END), 0) as cobros_hoy,
            COALESCE(SUM(CASE WHEN pag.estado = 'confirmado' THEN pag.monto_pagado ELSE 0 END), 0) as total_cobrado
        FROM usuarios u
        LEFT JOIN clientes c ON c.cobrador_id = u.id
        LEFT JOIN prestamos p ON p.cliente_id = c.id AND p.estado = 'activo'
        LEFT JOIN pagos pag ON pag.cobrador_id = u.id
        WHERE u.id = :id
    ");
    $statsStmt->execute(['id' => $id]);
    $stats = $statsStmt->fetch();
    
    $cobrador['estadisticas'] = [
        'total_clientes' => (int)$stats['total_clientes'],
        'clientes_activos' => (int)$stats['clientes_activos'],
        'total_prestamos' => (int)$stats['total_prestamos'],
        'cobros_hoy' => (float)$stats['cobros_hoy'],
        'total_cobrado' => (float)$stats['total_cobrado']
    ];
    
    Response::success($cobrador);
    
} catch (Exception $e) {
    error_log("Error al obtener cobrador: " . $e->getMessage());
    Response::serverError('Error al obtener cobrador');
}

