<?php
/**
 * Listar cobradores
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

$db = getDB();

try {
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Búsqueda
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    
    // Construir query
    $where = ["rol = 'cobrador'"];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(usuario LIKE :search OR nombre_completo LIKE :search2 OR email LIKE :search3)";
        $searchParam = '%' . $search . '%';
        $params['search'] = $searchParam;
        $params['search2'] = $searchParam;
        $params['search3'] = $searchParam;
    }
    
    if (!empty($estado)) {
        $where[] = "estado = :estado";
        $params['estado'] = $estado;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Contar total
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Obtener cobradores - consulta simplificada
    $sql = "
        SELECT 
            u.id,
            u.usuario,
            u.nombre_completo,
            u.email,
            u.rol,
            u.estado,
            u.created_at
        FROM usuarios u
        WHERE $whereClause
        ORDER BY u.nombre_completo ASC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $cobradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success([
        'cobradores' => $cobradores,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en listar cobradores: " . $e->getMessage());
    Response::serverError('Error al listar cobradores');
}

