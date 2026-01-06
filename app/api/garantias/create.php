<?php
/**
 * API: Crear garantía
 * POST /app/api/garantias/create.php
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
        'prestamo_id' => ['type' => 'integer','required' => true,'message' => 'prestamo_id requerido'],
        'tipo' => ['type' => 'string','required' => true,'message' => 'tipo requerido'],
        'descripcion' => ['type' => 'string','required' => false,'max' => 1000],
        'monto' => ['type' => 'number','required' => true,'min' => 0,'message' => 'monto requerido']
    ]);
    if (!$validation['valid']) { Response::validationError($validation['errors']); }

    $data = $validation['data'];
    $db = getDB();

    $stmt = $db->prepare('SELECT id, cliente_id FROM prestamos WHERE id = :id');
    $stmt->execute(['id' => $data['prestamo_id']]);
    $p = $stmt->fetch();
    if (!$p) { Response::error('Préstamo no encontrado', 404); }

    $stmt = $db->prepare('INSERT INTO garantias (prestamo_id, tipo, descripcion, monto) VALUES (:prestamo_id, :tipo, :descripcion, :monto)');
    $stmt->execute([
        'prestamo_id' => $data['prestamo_id'],
        'tipo' => Validator::sanitize($data['tipo']),
        'descripcion' => !empty($data['descripcion']) ? Validator::sanitize($data['descripcion']) : null,
        'monto' => round(floatval($data['monto']), 2)
    ]);
    $id = $db->lastInsertId();

    $stmt = $db->prepare('SELECT * FROM garantias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $gar = $stmt->fetch();

    Auth::logActivity($user['id'], 'create', 'garantias', 'Garantía creada', null, $gar);
    Response::success($gar, 'Garantía creada', 201);

} catch (Exception $e) {
    error_log('Error en garantias/create.php: ' . $e->getMessage());
    Response::serverError('Error al crear garantía');
}
