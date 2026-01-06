<?php
/**
 * Obtener colaborador por ID
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error('ID de colaborador requerido', 400);
}

$id = intval($_GET['id']);

try {
    // Obtener colaborador
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
        WHERE u.id = :id AND u.rol = 'colaborador'
    ");
    $stmt->execute(['id' => $id]);
    $colaborador = $stmt->fetch();
    
    if (!$colaborador) {
        Response::notFound('Colaborador no encontrado');
    }
    
    // Obtener estadísticas
    // Préstamos creados y Pagos recolectados
    $statsStmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM prestamos WHERE created_by = :id) as prestamos_creados,
            (SELECT COUNT(*) FROM pagos WHERE cobrado_por = :id) as pagos_registrados,
            (SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos WHERE cobrado_por = :id AND estado = 'confirmado') as total_cobrado,
            (SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos WHERE cobrado_por = :id AND estado = 'confirmado' AND DATE(fecha_pago) = CURDATE()) as cobros_hoy
    ");
    $statsStmt->execute(['id' => $id]);
    $stats = $statsStmt->fetch();
    
    $colaborador['estadisticas'] = [
        'prestamos_creados' => (int)$stats['prestamos_creados'],
        'pagos_registrados' => (int)$stats['pagos_registrados'],
        'total_cobrado' => (float)$stats['total_cobrado'],
        'cobros_hoy' => (float)$stats['cobros_hoy']
    ];
    
    Response::success($colaborador);
    
} catch (Exception $e) {
    error_log("Error al obtener colaborador: " . $e->getMessage());
    Response::serverError('Error al obtener colaborador');
}
