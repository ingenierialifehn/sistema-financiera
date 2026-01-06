<?php
/**
 * API: Actualizar referencia
 * PUT /app/api/referencias/update.php
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
        'nombre' => ['type' => 'string','required' => false],
        'telefono' => ['type' => 'string','required' => false],
        'parentesco' => ['type' => 'string','required' => false],
        'direccion' => ['type' => 'string','required' => false,'max' => 500]
    ]);
    if (!$validation['valid']) { Response::validationError($validation['errors']); }

    $db = getDB();

    $stmt = $db->prepare('SELECT * FROM referencias WHERE id = :id');
    $stmt->execute(['id' => intval($input['id'])]);
    $ref = $stmt->fetch();
    if (!$ref) { Response::notFound('Referencia no encontrada'); }

    $fields = [];$params = ['id' => $ref['id']];
    foreach (['nombre','telefono','parentesco','direccion'] as $f) {
        if (isset($input[$f])) { $fields[] = "$f = :$f"; $params[$f] = !empty($input[$f]) ? Validator::sanitize($input[$f]) : null; }
    }
    if (empty($fields)) { Response::error('No hay campos para actualizar', 400); }
    $fields[] = 'updated_at = NOW()';

    $sql = 'UPDATE referencias SET ' . implode(',', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $stmt = $db->prepare('SELECT * FROM referencias WHERE id = :id');
    $stmt->execute(['id' => $ref['id']]);
    $refUpd = $stmt->fetch();

    Auth::logActivity($user['id'], 'update', 'referencias', 'Referencia actualizada', $ref, $refUpd);
    Response::success($refUpd, 'Referencia actualizada');

} catch (Exception $e) {
    error_log('Error en referencias/update.php: ' . $e->getMessage());
    Response::serverError('Error al actualizar referencia');
}
