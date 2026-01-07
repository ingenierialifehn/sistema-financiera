<?php
/**
 * API: Obtener roles activos para select/dropdown
 * GET /app/api/roles/list_active.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

try {
    $db = getDB();

    $stmt = $db->query("
        SELECT id_rol, nombre_rol, descripcion 
        FROM roles 
        WHERE estado = 'Activo' 
        ORDER BY nombre_rol ASC
    ");

    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success($roles);

} catch (Exception $e) {
    Response::serverError('Error al obtener roles: ' . $e->getMessage());
}
