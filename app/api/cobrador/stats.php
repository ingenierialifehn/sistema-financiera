<?php
/**
 * Estadísticas del cobrador
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

$user = AuthMiddleware::requireAuth();

if ($user['rol'] !== 'cobrador' && $user['rol'] !== 'admin') {
    Response::forbidden('No autorizado');
}

$db = getDB();

try {
    // Cobros del día de hoy
    $stmt = $db->prepare("
        SELECT COUNT(*) as total, COALESCE(SUM(monto_pagado), 0) as monto_total
        FROM pagos
        WHERE cobrador_id = :cobrador_id
        AND DATE(fecha_pago) = CURDATE()
        AND estado = 'confirmado'
    ");
    $stmt->execute(['cobrador_id' => $user['id']]);
    $cobrosHoy = $stmt->fetch();
    
    // Total de clientes asignados
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM clientes
        WHERE cobrador_id = :cobrador_id
        AND estado = 'activo'
    ");
    $stmt->execute(['cobrador_id' => $user['id']]);
    $clientesAsignados = $stmt->fetch();
    
    // Préstamos activos de clientes asignados
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        WHERE c.cobrador_id = :cobrador_id
        AND p.estado = 'activo'
    ");
    $stmt->execute(['cobrador_id' => $user['id']]);
    $prestamosActivos = $stmt->fetch();
    
    Response::success([
        'cobros_hoy' => (int)$cobrosHoy['total'],
        'monto_hoy' => (float)$cobrosHoy['monto_total'],
        'clientes_asignados' => (int)$clientesAsignados['total'],
        'prestamos_activos' => (int)$prestamosActivos['total']
    ]);
    
} catch (Exception $e) {
    error_log("Error en stats cobrador: " . $e->getMessage());
    Response::serverError('Error al obtener estadísticas');
}

