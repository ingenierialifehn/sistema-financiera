<?php
/**
 * API: Actualizar ubicación del cliente (lat/lng)
 * PUT /app/api/clientes/update_location.php
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') { Response::error('Método no permitido', 405); }

try {
    $user = AuthMiddleware::requireCobradorOrAdmin();
    $input = getJsonInput();

    $validation = Validator::validate($input, [
        'cliente_id' => ['type' => 'integer','required' => true,'message' => 'cliente_id requerido'],
        'lat' => ['type' => 'number','required' => true,'message' => 'lat requerido'],
        'lng' => ['type' => 'number','required' => true,'message' => 'lng requerido']
    ]);
    if (!$validation['valid']) { Response::validationError($validation['errors']); }

    $db = getDB();

    $stmt = $db->prepare('SELECT * FROM clientes WHERE id = :id');
    $stmt->execute(['id' => intval($input['cliente_id'])]);
    $cli = $stmt->fetch();
    if (!$cli) { Response::notFound('Cliente no encontrado'); }

    $stmt = $db->prepare('UPDATE clientes SET lat = :lat, lng = :lng, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        'lat' => floatval($input['lat']),
        'lng' => floatval($input['lng']),
        'id' => intval($input['cliente_id'])
    ]);

    $stmt = $db->prepare('SELECT * FROM clientes WHERE id = :id');
    $stmt->execute(['id' => intval($input['cliente_id'])]);
    $upd = $stmt->fetch();

    Auth::logActivity($user['id'], 'update', 'clientes', 'Ubicación actualizada', $cli, $upd);
    Response::success($upd, 'Ubicación del cliente actualizada');

} catch (Exception $e) {
    error_log('Error en clientes/update_location.php: ' . $e->getMessage());
    Response::serverError('Error al actualizar ubicación');
}
