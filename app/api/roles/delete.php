<?php
/**
 * API: Eliminar Rol
 * DELETE /app/api/roles/delete.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth();
Auth::requirePermission('seguridad');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $input = getJsonInput();

    if (empty($input['id_rol'])) {
        Response::error('ID de rol requerido', 400);
    }

    $idRol = $input['id_rol'];
    $db = getDB();

    // Verificar que el rol existe
    $stmt = $db->prepare("SELECT nombre_rol FROM roles WHERE id_rol = ?");
    $stmt->execute([$idRol]);
    $rol = $stmt->fetch();

    if (!$rol) {
        Response::error('Rol no encontrado', 404);
    }

    // Verificar que no haya usuarios asignados a este rol
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE id_rol = ? AND estado = 'Activo'");
    $stmt->execute([$idRol]);
    $usuariosActivos = $stmt->fetchColumn();

    if ($usuariosActivos > 0) {
        Response::error("No se puede eliminar el rol '{$rol['nombre_rol']}' porque tiene $usuariosActivos usuario(s) activo(s) asignado(s)", 400);
    }

    // Eliminar rol
    $stmt = $db->prepare("DELETE FROM roles WHERE id_rol = ?");
    $stmt->execute([$idRol]);

    // Log
    Auth::logActivity($_SESSION['id_usuario'], 'delete', 'roles', "Eliminó el rol: {$rol['nombre_rol']}");

    Response::success(null, 'Rol eliminado exitosamente');

} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
