<?php
/**
 * API: Obtener cliente por ID
 * GET /app/api/clientes/get.php?id=1
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
    // Requerir autenticación
    $user = AuthMiddleware::requireAuth();

    // Validar ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        Response::error('ID de cliente es requerido', 400);
    }

    $id = intval($_GET['id']);

    $db = getDB();

    // Construir query con restricción de cobrador si aplica
    $sql = "
        SELECT 
            c.*,
            a.nombre_agencia as agencia_nombre,
            u.id_usuario as cobrador_id,
            col.nombre_completo as cobrador_nombre
        FROM clientes c
        LEFT JOIN usuarios u ON c.cobrador_id = u.id_usuario
        LEFT JOIN colaboradores col ON u.id_colaborador = col.id_colaborador
        LEFT JOIN agencias a ON c.id_agencia = a.id_agencia
        WHERE c.id = :id
    ";

    $params = ['id' => $id];

    // Si es cobrador, solo puede ver sus clientes
    if (isset($user['rol_nombre']) && $user['rol_nombre'] === 'cobrador') {
        $sql .= " AND c.cobrador_id = :cobrador_id";
        $params['cobrador_id'] = $user['id_usuario'];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $cliente = $stmt->fetch();

    require_once __DIR__ . '/../../core/ClienteHelper.php';

    if (!$cliente) {
        Response::notFound('Cliente no encontrado');
    }

    $riesgo = ClienteHelper::calcularCategoriaRiesgo($db, $cliente['id']);
    $cliente['categoria_riesgo'] = $riesgo['categoria'];
    $cliente['dias_mora_global'] = $riesgo['dias_mora'];

    Response::success($cliente, 'Cliente obtenido exitosamente');

} catch (Exception $e) {
    error_log("Error en clientes/get.php: " . $e->getMessage());
    Response::serverError('Error al obtener cliente');
}

