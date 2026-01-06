<?php
/**
 * API: Listar pagos
 * GET /app/api/pagos/list.php?page=1&limit=20&prestamo_id=&cliente_id=
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
    
    $db = getDB();
    
    // Parámetros
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $prestamoId = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : null;
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    
    // Construir query
    $where = [];
    $params = [];
    
    // Si es cobrador, solo mostrar sus pagos
    if ($user['rol'] === 'cobrador') {
        $where[] = "p.cobrado_por = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    }
    
    // Si es cliente, solo mostrar sus pagos
    if ($user['rol'] === 'cliente') {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $where[] = "p.cliente_id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            $where[] = "1 = 0";
        }
    }
    
    // Filtros
    if ($prestamoId) {
        $where[] = "p.prestamo_id = :prestamo_id";
        $params['prestamo_id'] = $prestamoId;
    }
    
    if ($clienteId) {
        $where[] = "p.cliente_id = :cliente_id_filter";
        $params['cliente_id_filter'] = $clienteId;
    }
    
    if (!empty($estado)) {
        $where[] = "p.estado = :estado";
        $params['estado'] = $estado;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM pagos p
        {$whereClause}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Obtener pagos
    $stmt = $db->prepare("
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
        {$whereClause}
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $pagos = $stmt->fetchAll();
    
    Response::success([
        'pagos' => $pagos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Pagos obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Error en pagos/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener pagos');
}

