<?php
/**
 * API: Listar clientes
 * GET /app/api/clientes/list.php?page=1&limit=20&search=
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación (admin o cobrador)
    $user = AuthMiddleware::requireCobradorOrAdmin();
    
    $db = getDB();
    
    // Parámetros de paginación y búsqueda
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Construir query
    $where = [];
    $params = [];
    
    // Si es cobrador, solo mostrar sus clientes asignados
    if ($user['rol'] === 'cobrador') {
        $where[] = "c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    }
    
    // Búsqueda
    if (!empty($search)) {
        $where[] = "(c.nombre_completo LIKE :search OR c.codigo_cliente LIKE :search OR c.numero_documento LIKE :search OR c.telefono LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM clientes c
        {$whereClause}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Obtener clientes
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.codigo_cliente,
            c.nombre_completo,
            c.tipo_documento,
            c.numero_documento,
            c.email,
            c.telefono,
            c.direccion,
            c.fecha_nacimiento,
            c.ocupacion,
            c.estado,
            c.created_at,
            u.id as cobrador_id,
            u.nombre_completo as cobrador_nombre
        FROM clientes c
        LEFT JOIN usuarios u ON c.cobrador_id = u.id
        {$whereClause}
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $clientes = $stmt->fetchAll();
    
    Response::success([
        'clientes' => $clientes,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Clientes obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Error en clientes/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener clientes');
}

