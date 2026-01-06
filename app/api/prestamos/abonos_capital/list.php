<?php
/**
 * API: Listar abonos a capital
 * GET /app/api/prestamos/abonos_capital/list.php?page=1&limit=20&prestamo_id=&cliente_id=&fecha_desde=&fecha_hasta=
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    $user = AuthMiddleware::requireAuth();

    $db = getDB();

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    $prestamoId = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : null;
    $clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : null;
    $fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

    $where = [];
    $params = [];

    if ($prestamoId) { $where[] = 'a.prestamo_id = :prestamo_id'; $params['prestamo_id'] = $prestamoId; }
    if ($clienteId) { $where[] = 'a.cliente_id = :cliente_id'; $params['cliente_id'] = $clienteId; }
    if ($fechaDesde) { $where[] = 'a.fecha >= :fecha_desde'; $params['fecha_desde'] = $fechaDesde; }
    if ($fechaHasta) { $where[] = 'a.fecha <= :fecha_hasta'; $params['fecha_hasta'] = $fechaHasta; }

    // Restricciones por rol
    if ($user['rol'] === 'cobrador') {
        $where[] = 'c.cobrador_id = :cobrador_id';
        $params['cobrador_id'] = $user['id'];
    } elseif ($user['rol'] === 'cliente') {
        $stmt = $db->prepare('SELECT id FROM clientes WHERE usuario_id = :uid');
        $stmt->execute(['uid' => $user['id']]);
        $cli = $stmt->fetch();
        if ($cli) { $where[] = 'a.cliente_id = :cliente_id_rol'; $params['cliente_id_rol'] = $cli['id']; }
        else { $where[] = '1 = 0'; }
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $count = $db->prepare("SELECT COUNT(*) AS total FROM abonos_capital a INNER JOIN clientes c ON a.cliente_id = c.id {$whereClause}");
    $count->execute($params);
    $total = (int)($count->fetch()['total'] ?? 0);

    $stmt = $db->prepare("SELECT a.*, c.nombre_completo AS cliente_nombre, p.numero_prestamo, u.nombre_completo AS registrado_por_nombre
                           FROM abonos_capital a
                           INNER JOIN clientes c ON a.cliente_id = c.id
                           INNER JOIN prestamos p ON a.prestamo_id = p.id
                           LEFT JOIN usuarios u ON a.registrado_por = u.id
                           {$whereClause}
                           ORDER BY a.fecha DESC, a.created_at DESC
                           LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) { $stmt->bindValue(':'.$k, $v); }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    Response::success([
        'abonos' => $rows,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Abonos obtenidos exitosamente');

} catch (Exception $e) {
    error_log('Error en abonos_capital/list.php: ' . $e->getMessage());
    Response::serverError('Error al obtener abonos');
}
