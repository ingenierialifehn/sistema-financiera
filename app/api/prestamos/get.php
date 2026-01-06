<?php
/**
 * API: Obtener préstamo por ID
 * GET /app/api/prestamos/get.php?id=1
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
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        Response::error('ID de préstamo es requerido', 400);
    }
    
    $id = intval($_GET['id']);
    $db = getDB();
    
    // Construir query con restricciones de rol
    $sql = "
        SELECT 
            p.*,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            c.telefono as cliente_telefono,
            COUNT(cu.id) as total_cuotas,
            SUM(CASE WHEN cu.estado = 'pagada' THEN 1 ELSE 0 END) as cuotas_pagadas,
            SUM(CASE WHEN cu.estado IN ('pendiente', 'en_mora') THEN 1 ELSE 0 END) as cuotas_pendientes,
            SUM(cu.monto_pagado) as monto_pagado_total,
            (p.monto_total - COALESCE(SUM(cu.monto_pagado), 0)) as saldo_pendiente
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN cuotas cu ON p.id = cu.prestamo_id
        WHERE p.id = :id
    ";
    
    $params = ['id' => $id];
    
    if ($user['rol'] === 'cobrador') {
        $sql .= " AND c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    } elseif ($user['rol'] === 'cliente') {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $sql .= " AND p.cliente_id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            Response::notFound('Préstamo no encontrado');
        }
    }
    
    $sql .= " GROUP BY p.id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $prestamo = $stmt->fetch();
    
    if (!$prestamo) {
        Response::notFound('Préstamo no encontrado');
    }
    
    // Obtener cuotas
    $stmt = $db->prepare("
        SELECT * FROM cuotas 
        WHERE prestamo_id = :id 
        ORDER BY numero_cuota ASC
    ");
    $stmt->execute(['id' => $id]);
    $prestamo['cuotas'] = $stmt->fetchAll();
    
    Response::success($prestamo, 'Préstamo obtenido exitosamente');
    
} catch (Exception $e) {
    error_log("Error en prestamos/get.php: " . $e->getMessage());
    Response::serverError('Error al obtener préstamo');
}

