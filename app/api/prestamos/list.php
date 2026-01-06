<?php
/**
 * API: Listar préstamos
 * GET /app/api/prestamos/list.php?page=1&limit=20&search=&estado=
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
    // Requerir autenticación
    $user = AuthMiddleware::requireAuth();
    
    $db = getDB();
    
    // Parámetros
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
    
    // Construir query
    $where = [];
    $params = [];
    
    // Si es cobrador, solo mostrar préstamos de sus clientes
    if ($user['rol'] === 'cobrador') {
        $where[] = "c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $user['id'];
    }
    
    // Si es cliente, solo mostrar sus préstamos
    if ($user['rol'] === 'cliente') {
        // Buscar cliente_id del usuario
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $user['id']]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $where[] = "p.cliente_id = :cliente_id";
            $params['cliente_id'] = $cliente['id'];
        } else {
            // Si no tiene cliente asociado, no mostrar nada
            $where[] = "1 = 0";
        }
    }
    
    // Filtro por cliente
    if ($clienteId) {
        $where[] = "p.cliente_id = :cliente_id_filter";
        $params['cliente_id_filter'] = $clienteId;
    }
    
    // Filtro por estado
    if (!empty($estado)) {
        $where[] = "p.estado = :estado";
        $params['estado'] = $estado;
    }
    
    // Búsqueda
    if (!empty($search)) {
        $where[] = "(p.numero_prestamo LIKE :search OR c.nombre_completo LIKE :search OR c.codigo_cliente LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        {$whereClause}
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Obtener préstamos con información de cuotas
    $stmt = $db->prepare("
        SELECT 
            p.*,
            c.nombre_completo as cliente_nombre,
            c.codigo_cliente,
            COUNT(cu.id) as total_cuotas,
            SUM(CASE WHEN cu.estado = 'pagada' THEN 1 ELSE 0 END) as cuotas_pagadas,
            SUM(CASE WHEN cu.estado IN ('pendiente', 'en_mora') THEN 1 ELSE 0 END) as cuotas_pendientes,
            SUM(cu.monto_pagado) as monto_pagado_total,
            (p.monto_total - COALESCE(SUM(cu.monto_pagado), 0)) as saldo_pendiente
        FROM prestamos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN cuotas cu ON p.id = cu.prestamo_id
        {$whereClause}
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $prestamos = $stmt->fetchAll();
    
    Response::success([
        'prestamos' => $prestamos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Préstamos obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Error en prestamos/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener préstamos');
}

