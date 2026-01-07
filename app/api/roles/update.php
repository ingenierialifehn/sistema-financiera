<?php
/**
 * API: Actualizar Rol
 * PUT /app/api/roles/update.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth();
Auth::requirePermission('seguridad');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $input = getJsonInput();

    // Validación básica
    if (empty($input['id_rol'])) {
        Response::error('ID de rol requerido', 400);
    }

    if (empty($input['nombre_rol'])) {
        Response::error('El nombre del rol es requerido', 400);
    }

    if (empty($input['permisos']) || !is_array($input['permisos'])) {
        Response::error('Debe seleccionar al menos un permiso', 400);
    }

    if (strlen($input['nombre_rol']) < 3) {
        Response::error('El nombre del rol debe tener al menos 3 caracteres', 400);
    }

    $idRol = $input['id_rol'];
    $nombreRol = trim($input['nombre_rol']);
    $permisos = $input['permisos'];
    $permisosJson = json_encode($permisos);
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : '';

    $db = getDB();

    // Verificar que el rol existe
    $stmt = $db->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
    $stmt->execute([$idRol]);
    if (!$stmt->fetch()) {
        Response::error('Rol no encontrado', 404);
    }

    // Verificar nombre único (excepto el mismo rol)
    $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = ? AND id_rol != ?");
    $stmt->execute([$nombreRol, $idRol]);
    if ($stmt->fetchColumn() > 0) {
        Response::error('El nombre del rol ya existe', 400);
    }

    // Actualizar rol
    $stmt = $db->prepare("
        UPDATE roles 
        SET nombre_rol = :nombre, 
            descripcion = :descripcion, 
            permisos = :permisos,
            updated_at = CURRENT_TIMESTAMP
        WHERE id_rol = :id
    ");

    $stmt->execute([
        'nombre' => $nombreRol,
        'descripcion' => $descripcion,
        'permisos' => $permisosJson,
        'id' => $idRol
    ]);

    // Log
    Auth::logActivity($_SESSION['id_usuario'], 'update', 'roles', "Actualizó el rol: $nombreRol");

    Response::success(['id_rol' => $idRol], 'Rol actualizado exitosamente');

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
