<?php
/**
 * API: Crear puesto
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';

Auth::requireAuth(); // Adjust permission as needed

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = getJsonInput();

if (empty($data['nombre_puesto'])) {
    Response::error('El nombre del puesto es obligatorio', 400);
}

try {
    $db = getDB();

    // Check duplicate
    $stmtCheck = $db->prepare("SELECT id_puesto FROM lista_puestos WHERE nombre_puesto = :nombre");
    $stmtCheck->execute(['nombre' => $data['nombre_puesto']]);
    if ($stmtCheck->fetch()) {
        Response::error('El puesto ya existe', 400);
    }

    $stmt = $db->prepare("INSERT INTO lista_puestos (nombre_puesto, estado) VALUES (:nombre, :estado)");
    $stmt->execute([
        'nombre' => $data['nombre_puesto'],
        'estado' => $data['estado'] ?? 'Activo'
    ]);

    Response::success(['id' => $db->lastInsertId()], 'Puesto creado correctamente');
} catch (Exception $e) {
    Response::serverError($e->getMessage());
}
