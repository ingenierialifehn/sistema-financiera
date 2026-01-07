<?php
/**
 * Actualizar Agencia
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id_agencia']) || empty($data['nombre_agencia'])) {
        Response::error('ID y nombre de la agencia son obligatorios', 400);
    }

    $db = getDB();
    $db->beginTransaction();

    $stmt = $db->prepare("
        UPDATE agencias 
        SET nombre_agencia = :nombre,
            direccion = :direccion,
            ciudad = :ciudad,
            telefono_agencia = :telefono,
            estado = :estado
        WHERE id_agencia = :id
    ");

    $stmt->execute([
        ':id' => $data['id_agencia'],
        ':nombre' => $data['nombre_agencia'],
        ':direccion' => $data['direccion'] ?? null,
        ':ciudad' => $data['ciudad'] ?? null,
        ':telefono' => $data['telefono_agencia'] ?? null,
        ':estado' => $data['estado'] ?? 'Activa'
    ]);

    $db->commit();

    Response::success(['message' => 'Agencia actualizada exitosamente']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error al actualizar agencia: " . $e->getMessage());
    Response::serverError('Error al actualizar agencia');
}
