<?php
/**
 * API: Listar usuarios activos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    $db = getDB();

    $sql = "SELECT u.id_usuario, u.username, u.email, u.estado,
            r.nombre_rol as rol_nombre,
            COALESCE(c.nombre_completo, u.nombre_completo, u.username) as nombre_completo,
            COALESCE(c.puesto_cargo, r.nombre_rol, 'Sin Puesto') as puesto_cargo
            FROM usuarios u
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
            WHERE u.estado = 'Activo'
            ORDER BY u.username ASC";

    $stmt = $db->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $usuarios
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>