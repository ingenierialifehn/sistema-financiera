<?php
/**
 * API: Obtener un rol específico
 * GET /app/api/roles/get.php?id=1
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('seguridad');

if (!isset($_GET['id'])) {
    Response::error('ID de rol requerido', 400);
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM roles WHERE id_rol = ?");
    $stmt->execute([$_GET['id']]);
    $rol = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rol) {
        Response::error('Rol no encontrado', 404);
    }

    // Decodificar JSON de permisos
    $rol['permisos'] = json_decode($rol['permisos'], true);

    Response::success($rol);

} catch (Exception $e) {
    Response::serverError('Error al obtener rol: ' . $e->getMessage());
}
