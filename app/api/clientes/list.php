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
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 1000; // Aumentado para mostrar todos
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $agencia = isset($_GET['agencia']) ? intval($_GET['agencia']) : null;

    // Construir query
    $where = [];
    $params = [];

    // Si es cobrador, solo mostrar sus clientes asignados
    // Si es admin/gerente, puede ver todos (filtro opcional por agencia)
    // Determinar ALCANCE DE DATOS (Scope)
    // 1. Verificar si tiene acceso Global
    $canViewGlobal = Auth::hasPermission('special_scopes.clientes_view_global');

    // 2. Verificar si tiene acceso de Agencia
    $canViewAgency = Auth::hasPermission('special_scopes.clientes_view_agency');

    if ($canViewGlobal) {
        // Acceso Global: No aplicamos filtros restrictivos automáticos.
        // El usuario puede ver todo. Los filtros por GET (abajo) seguirán funcionando si se envían.
        // Si el admin envía ?cobrador_id=X, funcionará.
    } elseif ($canViewAgency) {
        // Acceso Agencia: Restringir a la agencia del usuario
        // Asumimos que el usuario tiene id_agencia. Si no, fallback a propio.
        if (!empty($user['id_agencia'])) {
            $where[] = "c.id_agencia = :current_user_scope_agency";
            $params['current_user_scope_agency'] = $user['id_agencia'];
        } else {
            // Fallback si no tiene agencia asignada
            $where[] = "c.cobrador_id = :current_user_id_fallback";
            $params['current_user_id_fallback'] = $user['id_usuario'];
        }
    } else {
        // Acceso Propio (Default): Solo ver clientes asignados a él
        $where[] = "c.cobrador_id = :current_user_id";
        $params['current_user_id'] = $user['id_usuario'];
    }

    /*
    // Lógica anterior (Roles) - DESHABILITADA
    if ($rol === 'cobrador') { ... }
    */

    // Búsqueda
    if (!empty($search)) {
        $where[] = "(c.nombre_completo LIKE :search1 OR c.codigo_cliente LIKE :search2 OR c.numero_documento LIKE :search3 OR c.telefono LIKE :search4)";
        $params['search1'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
        $params['search3'] = "%{$search}%";
        $params['search4'] = "%{$search}%";
    }

    // Filtro por estado
    if (!empty($estado)) {
        $where[] = "c.estado = :estado";
        $params['estado'] = $estado;
    }

    // Filtro por agencia
    if ($agencia) {
        $where[] = "c.id_agencia = :agencia";
        $params['agencia'] = $agencia;
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

    // Obtener clientes - Solo columnas que existen en la tabla actual
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.usuario_id,
            c.codigo_cliente,
            c.nombre_completo,
            c.tipo_documento,
            c.numero_documento,
            c.email,
            c.telefono,
            c.direccion,
            c.fecha_nacimiento,
            c.ocupacion,
            c.referencia_personal,
            c.telefono_referencia,
            c.foto_documento,
            c.foto_perfil,
            c.estado,
            c.cobrador_id,
            c.id_agencia,
            c.created_at,
            c.updated_at,
            a.nombre_agencia as agencia_nombre
        FROM clientes c
        LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
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

    // Agregar campos que no existen en la tabla pero el frontend espera
    // Agregar campos calculados y categoría de riesgo
    require_once __DIR__ . '/../../core/ClienteHelper.php';

    foreach ($clientes as &$cliente) {
        // foto_perfil ya viene de la base de datos
        // Si está vacío, usar foto_documento como fallback
        if (empty($cliente['foto_perfil']) && !empty($cliente['foto_documento'])) {
            $cliente['foto_perfil'] = $cliente['foto_documento'];
        }

        // agencia_nombre ya viene del JOIN
        if (!$cliente['agencia_nombre']) {
            $cliente['agencia_nombre'] = 'Sin asignar';
        }

        $cliente['cobrador_nombre'] = null;

        $riesgo = ClienteHelper::calcularCategoriaRiesgo($db, $cliente['id']);
        $cliente['categoria_riesgo'] = $riesgo['categoria'];
        $cliente['dias_mora_global'] = $riesgo['dias_mora'];
    }

    Response::success([
        'clientes' => $clientes,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Clientes obtenidos exitosamente');

} catch (Exception $e) {
    error_log("Error en clientes/list.php: " . $e->getMessage());
    Response::serverError('Error al obtener clientes');
}
