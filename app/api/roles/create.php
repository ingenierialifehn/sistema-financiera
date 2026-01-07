<?php
/**
 * API: Crear Rol
 * POST /app/api/roles/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth();
Auth::requirePermission('seguridad');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $input = getJsonInput();

    // Validación básica
    if (empty($input['nombre_rol'])) {
        Response::error('El nombre del rol es requerido', 400);
    }

    if (empty($input['permisos']) || !is_array($input['permisos'])) {
        Response::error('Debe seleccionar al menos un permiso', 400);
    }

    if (strlen($input['nombre_rol']) < 3) {
        Response::error('El nombre del rol debe tener al menos 3 caracteres', 400);
    }

    $nombreRol = trim($input['nombre_rol']);
    $permisos = $input['permisos']; // Array de permisos
    $permisosJson = json_encode($permisos);
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : '';

    $db = getDB();

    // Verificar nombre único
    $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = ?");
    $stmt->execute([$nombreRol]);
    if ($stmt->fetchColumn() > 0) {
        Response::error('El nombre del rol ya existe', 400);
    }

    $stmt = $db->prepare("
        INSERT INTO roles (nombre_rol, descripcion, permisos, estado) 
        VALUES (:nombre, :descripcion, :permisos, 'Activo')
    ");

    $stmt->execute([
        'nombre' => $nombreRol,
        'descripcion' => $descripcion,
        'permisos' => $permisosJson
    ]);

    $id = $db->lastInsertId();

    // Log
    Auth::logActivity($_SESSION['user_id'], 'create', 'roles', "Creó el rol: $nombreRol");

    Response::success(['id' => $id], 'Rol creado exitosamente');

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
