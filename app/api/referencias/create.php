<?php
/**
 * API: Crear referencia personal
 * POST /app/api/referencias/create.php
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { Response::error('Método no permitido', 405); }

try {
    $user = AuthMiddleware::requireCobradorOrAdmin();
    $input = getJsonInput();

    $validation = Validator::validate($input, [
        'cliente_id' => ['type' => 'integer','required' => true,'message' => 'cliente_id requerido'],
        'nombre' => ['type' => 'string','required' => true,'message' => 'nombre requerido'],
        'telefono' => ['type' => 'string','required' => true,'message' => 'telefono requerido'],
        'parentesco' => ['type' => 'string','required' => false],
        'direccion' => ['type' => 'string','required' => false,'max' => 500]
    ]);
    if (!$validation['valid']) { Response::validationError($validation['errors']); }

    $data = $validation['data'];
    $db = getDB();

    // verificar cliente
    $stmt = $db->prepare('SELECT id FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $data['cliente_id']]);
    if (!$stmt->fetch()) { Response::error('Cliente no encontrado', 404); }

    $stmt = $db->prepare('INSERT INTO referencias (cliente_id, nombre, telefono, parentesco, direccion) VALUES (:cliente_id, :nombre, :telefono, :parentesco, :direccion)');
    $stmt->execute([
        'cliente_id' => $data['cliente_id'],
        'nombre' => Validator::sanitize($data['nombre']),
        'telefono' => Validator::sanitize($data['telefono']),
        'parentesco' => !empty($data['parentesco']) ? Validator::sanitize($data['parentesco']) : null,
        'direccion' => !empty($data['direccion']) ? Validator::sanitize($data['direccion']) : null
    ]);
    $id = $db->lastInsertId();

    $stmt = $db->prepare('SELECT * FROM referencias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $ref = $stmt->fetch();

    Auth::logActivity($user['id'], 'create', 'referencias', 'Referencia creada', null, $ref);
    Response::success($ref, 'Referencia creada', 201);

} catch (Exception $e) {
    error_log('Error en referencias/create.php: ' . $e->getMessage());
    Response::serverError('Error al crear referencia');
}
