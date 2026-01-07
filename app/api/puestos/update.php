<?php
/**
 * API: Actualizar puesto
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (empty($data['id_puesto']) || empty($data['nombre_puesto'])) {
    Response::error('ID y nombre requeridos', 400);
}

try {
    $db = getDB();

    // Check duplicate name excluding self
    $stmtCheck = $db->prepare("SELECT id_puesto FROM lista_puestos WHERE nombre_puesto = :nombre AND id_puesto != :id");
    $stmtCheck->execute(['nombre' => $data['nombre_puesto'], 'id' => $data['id_puesto']]);
    if ($stmtCheck->fetch()) {
        Response::error('Ya existe otro puesto con este nombre', 400);
    }

    $stmt = $db->prepare("UPDATE lista_puestos SET nombre_puesto = :nombre, estado = :estado WHERE id_puesto = :id");
    $stmt->execute([
        'nombre' => $data['nombre_puesto'],
        'estado' => $data['estado'] ?? 'Activo',
        'id' => $data['id_puesto']
    ]);

    Response::success([], 'Puesto actualizado correctamente');
} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
