<?php
/**
 * Crear Agencia
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['nombre_agencia'])) {
        Response::error('El nombre de la agencia es obligatorio', 400);
    }

    $db = getDB();
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO agencias (nombre_agencia, direccion, ciudad, telefono_agencia, estado)
        VALUES (:nombre, :direccion, :ciudad, :telefono, :estado)
    ");

    $stmt->execute([
        ':nombre' => $data['nombre_agencia'],
        ':direccion' => $data['direccion'] ?? null,
        ':ciudad' => $data['ciudad'] ?? null,
        ':telefono' => $data['telefono_agencia'] ?? null,
        ':estado' => $data['estado'] ?? 'Activa'
    ]);

    $id = $db->lastInsertId();
    $db->commit();

    Response::success(['id_agencia' => $id, 'message' => 'Agencia creada exitosamente']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error al crear agencia: " . $e->getMessage());
    Response::serverError('Error al crear agencia: ' . $e->getMessage());
}
