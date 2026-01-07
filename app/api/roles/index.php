<?php
/**
 * API: Obtener lista de roles
 * GET /app/api/roles/index.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

// Validar autenticación
Auth::requireAuth();

// Validar permiso de ver roles (o seguridad)
Auth::requirePermission('seguridad');

try {
    $db = getDB();

    $stmt = $db->query("SELECT * FROM roles ORDER BY id_rol ASC");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decodificar JSON de permisos
    foreach ($roles as &$rol) {
        $rol['permisos'] = json_decode($rol['permisos'], true);
    }

    Response::success($roles);

} catch (Exception $e) {
    Response::serverError('Error al obtener roles: ' . $e->getMessage());
}
