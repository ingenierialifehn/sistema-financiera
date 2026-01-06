<?php
/**
 * API: Obtener cuotas de un préstamo
 * GET /app/api/prestamos/cuotas.php?prestamo_id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();
    
    if (!isset($_GET['prestamo_id']) || empty($_GET['prestamo_id'])) {
        Response::error('ID de préstamo es requerido', 400);
    }
    
    $prestamoId = intval($_GET['prestamo_id']);
    $db = getDB();
    
    // Verificar que el préstamo existe y tiene permisos
    $sql = "SELECT p.* FROM prestamos p WHERE p.id = :id";
    $params = ['id' => $prestamoId];
    
    if ($user['rol'] === 'cobrador') {
        $sql = "SELECT p.* 
                FROM prestamos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.id = :id AND c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    } elseif ($user['rol'] === 'cliente') {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $sql = "SELECT p.* FROM prestamos p WHERE p.id = :id AND p.cliente_id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            Response::notFound('Préstamo no encontrado');
        }
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    if (!$stmt->fetch()) {
        Response::notFound('Préstamo no encontrado');
    }
    
    // Obtener cuotas
    $stmt = $db->prepare("
        SELECT 
            cu.*,
            COUNT(pg.id) as pagos_count,
            SUM(pg.monto_pagado) as total_pagado
        FROM cuotas cu
        LEFT JOIN pagos pg ON cu.id = pg.cuota_id AND pg.estado = 'confirmado'
        WHERE cu.prestamo_id = :prestamo_id
        GROUP BY cu.id
        ORDER BY cu.numero_cuota ASC
    ");
    $stmt->execute(['prestamo_id' => $prestamoId]);
    $cuotas = $stmt->fetchAll();
    
    Response::success(['cuotas' => $cuotas], 'Cuotas obtenidas exitosamente');
    
} catch (Exception $e) {
    error_log("Error en prestamos/cuotas.php: " . $e->getMessage());
    Response::serverError('Error al obtener cuotas');
}

