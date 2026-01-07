<?php
/**
 * Obtener Agencia por ID
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

if (!isset($_GET['id'])) {
    Response::error('ID requerido', 400);
}

$id = $_GET['id'];

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM agencias WHERE id_agencia = ?");
    $stmt->execute([$id]);
    $agencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agencia) {
        Response::notFound('Agencia no encontrada');
    }

    Response::success($agencia);

} catch (Exception $e) {
    error_log("Error al obtener agencia: " . $e->getMessage());
    Response::serverError('Error interno');
}
