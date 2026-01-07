<?php
/**
 * Listar colaboradores (Gestión de Personal)
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

    // Filtros
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $agencia = isset($_GET['agencia']) ? trim($_GET['agencia']) : '';
    $puesto = isset($_GET['puesto']) ? trim($_GET['puesto']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

    // Construir query base
    $where = ["1=1"];
    $params = [];

    if (!empty($search)) {
        // Buscamos por nombre, DNI o email
        $where[] = "(c.nombre_completo LIKE :search OR c.dni LIKE :search2 OR c.email LIKE :search3)";
        $searchParam = '%' . $search . '%';
        $params['search'] = $searchParam;
        $params['search2'] = $searchParam;
        $params['search3'] = $searchParam;
    }

    if (!empty($agencia)) {
        $where[] = "c.id_agencia = :agencia";
        $params['agencia'] = $agencia;
    }

    if (!empty($puesto)) {
        $where[] = "c.puesto_cargo = :puesto";
        $params['puesto'] = $puesto;
    }

    if (!empty($estado)) { // estado_laboral
        $where[] = "c.estado_laboral = :estado";
        $params['estado'] = $estado;
    }

    $whereClause = implode(' AND ', $where);

    // Contar total
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM colaboradores c WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Obtener colaboradores con info de usuario si existe
    $sql = "
        SELECT 
            c.id_colaborador as id,
            c.dni,
            c.nombre_completo,
            c.email,
            c.puesto_cargo as puesto,
            c.id_agencia,
            a.nombre_agencia as agencia,
            c.estado_laboral as estado,
            c.estado_laboral,
            u.id_usuario as usuario_id,
            u.username as usuario_nombre,
            u.estado as usuario_estado
        FROM colaboradores c
        LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
        LEFT JOIN usuarios u ON u.id_colaborador = c.id_colaborador
        WHERE $whereClause
        ORDER BY c.nombre_completo ASC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success([
        'colaboradores' => $colaboradores,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en listar colaboradores: " . $e->getMessage());
    Response::serverError('Error al listar colaboradores');
}
