<?php
/**
 * API: Obtener pago por ID
 * GET /app/api/pagos/get.php?id=1
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
        Response::error('ID de pago es requerido', 400);
    }
    
    $id = intval($_GET['id']);
    $db = getDB();
    
    // Construir query con restricciones
    $sql = "
        SELECT 
            p.*,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            pr.numero_prestamo,
            cu.numero_cuota,
            u.nombre_completo as cobrador_nombre
        FROM pagos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        INNER JOIN prestamos pr ON p.prestamo_id = pr.id
        INNER JOIN cuotas cu ON p.cuota_id = cu.id
        LEFT JOIN usuarios u ON p.cobrado_por = u.id
        WHERE p.id = :id
    ";
    
    $params = ['id' => $id];
    
    if ($user['rol'] === 'cobrador') {
        $sql .= " AND p.cobrado_por = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    } elseif ($user['rol'] === 'cliente') {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $sql .= " AND p.cliente_id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            Response::notFound('Pago no encontrado');
        }
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pago = $stmt->fetch();
    
    if (!$pago) {
        Response::notFound('Pago no encontrado');
    }
    
    Response::success($pago, 'Pago obtenido exitosamente');
    
} catch (Exception $e) {
    error_log("Error en pagos/get.php: " . $e->getMessage());
    Response::serverError('Error al obtener pago');
}

