<?php
/**
 * API: Eliminar puesto (Soft delete or check dependencies?)
 * User requested "Gestión de Puestos", implies delete.
 * But if collaborators adhere to these names, we shouldn't simple delete.
 * However, the prompt says "agregar, editar o eliminar".
 * I'll just do a hard delete but check usage first? 
 * Actually, the collaborators table stores 'puesto_cargo' as VARCHAR.
 * So deleting here doesn't break foreign keys, but it creates inconsistency.
 * Given existing data is 'Gerente', checking if usage exists is good practice.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Using POST for delete action usually
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (empty($data['id_puesto'])) {
    Response::error('ID requerido', 400);
}

try {
    $db = getDB();

    // Get name first
    $stmtName = $db->prepare("SELECT nombre_puesto FROM lista_puestos WHERE id_puesto = :id");
    $stmtName->execute(['id' => $data['id_puesto']]);
    $puesto = $stmtName->fetch(PDO::FETCH_ASSOC);

    if (!$puesto) {
        Response::error('Puesto no encontrado', 404);
    }

    // Check usage
    $stmtUsage = $db->prepare("SELECT count(*) as total FROM collaborators WHERE puesto_cargo = :nombre");
    // Wait, table is `colaboradores`
    $stmtUsage = $db->prepare("SELECT count(*) as total FROM colaboradores WHERE puesto_cargo = :nombre");
    $stmtUsage->execute(['nombre' => $puesto['nombre_puesto']]);

    if ($stmtUsage->fetch()['total'] > 0) {
        Response::error('No se puede eliminar el puesto porque hay colaboradores asignados a él. Intente desactivarlo.', 400);
    }

    $stmt = $db->prepare("DELETE FROM lista_puestos WHERE id_puesto = :id");
    $stmt->execute(['id' => $data['id_puesto']]);

    Response::success([], 'Puesto eliminado correctamente');
} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
