<?php
/**
 * API: Listar usuarios activos
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    $db = getDB();

    $agenciaId = $_GET['agencia_id'] ?? null;

    $sql = "SELECT u.id_usuario, u.username, u.estado,
            c.email,
            c.id_agencia,
            r.nombre_rol as rol_nombre,
            r.permisos,
            COALESCE(c.nombre_completo, u.username) as nombre_completo,
            COALESCE(c.puesto_cargo, r.nombre_rol, 'Sin Puesto') as puesto_cargo
            FROM usuarios u
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            LEFT JOIN colaboradores c ON u.id_colaborador = c.id_colaborador
            WHERE u.estado = 'Activo'";

    $params = [];
    if ($agenciaId) {
        $sql .= " AND c.id_agencia = ?";
        $params[] = $agenciaId;
    }

    $sql .= " ORDER BY u.username ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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