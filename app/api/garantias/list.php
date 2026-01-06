<?php
/**
 * API: Listar garantías
 * GET /app/api/garantias/list.php?prestamo_id=&page=&limit=
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { Response::error('Método no permitido', 405); }

try {
    $user = AuthMiddleware::requireAuth();
    $db = getDB();

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $prestamoId = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : null;

    $where = [];$params = [];
    if ($prestamoId) { $where[] = 'g.prestamo_id = :prestamo_id'; $params['prestamo_id'] = $prestamoId; }

    if ($user['rol'] === 'cobrador') {
        $where[] = 'c.cobrador_id = :cobrador_id';
        $params['cobrador_id'] = $user['id'];
    } elseif ($user['rol'] === 'cliente') {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE usuario_id = :uid');
        $stmt->execute(['uid' => $user['id']]);
        $cli = $stmt->fetch();
        if ($cli) { $where[] = 'p.cliente_id = :cliente_id_rol'; $params['cliente_id_rol'] = $cli['id']; } else { $where[] = '1 = 0'; }
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $count = $db->prepare("SELECT COUNT(*) AS total FROM garantias g INNER JOIN prestamos p ON g.prestamo_id = p.id INNER JOIN clientes c ON p.cliente_id = c.id {$whereClause}");
    $count->execute($params); $total = (int)($count->fetch()['total'] ?? 0);

    $stmt = $db->prepare("SELECT g.* FROM garantias g INNER JOIN prestamos p ON g.prestamo_id = p.id INNER JOIN clientes c ON p.cliente_id = c.id {$whereClause} ORDER BY g.created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) { $stmt->bindValue(':'.$k, $v); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    Response::success(['garantias' => $rows, 'pagination' => ['page'=>$page,'limit'=>$limit,'total'=>$total,'total_pages'=>ceil($total/$limit)]], 'Garantías obtenidas');

} catch (Exception $e) {
    error_log('Error en garantias/list.php: ' . $e->getMessage());
    Response::serverError('Error al obtener garantías');
}
