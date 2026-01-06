<?php
/**
 * API: Eliminar referencia
 * DELETE /app/api/referencias/delete.php?id=
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') { Response::error('Método no permitido', 405); }

try {
    $user = AuthMiddleware::requireCobradorOrAdmin();

    $input = getJsonInput();
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : null);
    if (!$id) { Response::error('ID requerido', 400); }

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM referencias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $ref = $stmt->fetch();
    if (!$ref) { Response::notFound('Referencia no encontrada'); }

    $db->prepare('DELETE FROM referencias WHERE id = :id')->execute(['id' => $id]);

    Auth::logActivity($user['id'], 'delete', 'referencias', 'Referencia eliminada', $ref, null);
    Response::success(null, 'Referencia eliminada');

} catch (Exception $e) {
    error_log('Error en referencias/delete.php: ' . $e->getMessage());
    Response::serverError('Error al eliminar referencia');
}
