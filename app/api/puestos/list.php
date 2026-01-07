<?php
/**
 * API: Listar puestos
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

try {
    $db = getDB();
    $onlyActive = isset($_GET['active']) && $_GET['active'] === 'true';

    $sql = "SELECT * FROM lista_puestos";
    if ($onlyActive) {
        $sql .= " WHERE estado = 'Activo'";
    }
    $sql .= " ORDER BY nombre_puesto ASC";

    $stmt = $db->query($sql);
    $puestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success($puestos);
} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
