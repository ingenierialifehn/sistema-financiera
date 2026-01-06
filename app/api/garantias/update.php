<?php
/**
 * API: Actualizar garantía
 * PUT /app/api/garantias/update.php
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
    if (!isset($input['id'])) { Response::error('ID requerido', 400); }

    $validation = Validator::validate($input, [
        'tipo' => ['type' => 'string','required' => false],
        'descripcion' => ['type' => 'string','required' => false,'max' => 1000],
        'monto' => ['type' => 'number','required' => false,'min' => 0]
    ]);
    if (!$validation['valid']) { Response::validationError($validation['errors']); }

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM garantias WHERE id = :id');
    $stmt->execute(['id' => intval($input['id'])]);
    $gar = $stmt->fetch();
    if (!$gar) { Response::notFound('Garantía no encontrada'); }

    $fields = [];$params = ['id' => $gar['id']];
    if (isset($input['tipo'])) { $fields[] = 'tipo = :tipo'; $params['tipo'] = Validator::sanitize($input['tipo']); }
    if (isset($input['descripcion'])) { $fields[] = 'descripcion = :descripcion'; $params['descripcion'] = !empty($input['descripcion']) ? Validator::sanitize($input['descripcion']) : null; }
    if (isset($input['monto'])) { $fields[] = 'monto = :monto'; $params['monto'] = round(floatval($input['monto']), 2); }
    if (empty($fields)) { Response::error('No hay campos para actualizar', 400); }
    $fields[] = 'updated_at = NOW()';

    $sql = 'UPDATE garantias SET ' . implode(',', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $stmt = $db->prepare('SELECT * FROM garantias WHERE id = :id');
    $stmt->execute(['id' => $gar['id']]);
    $garUpd = $stmt->fetch();

    Auth::logActivity($user['id'], 'update', 'garantias', 'Garantía actualizada', $gar, $garUpd);
    Response::success($garUpd, 'Garantía actualizada');

} catch (Exception $e) {
    error_log('Error en garantias/update.php: ' . $e->getMessage());
    Response::serverError('Error al actualizar garantía');
}
