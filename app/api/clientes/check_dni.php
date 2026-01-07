<?php
/**
 * API: Verificar si un DNI ya existe
 * GET /app/api/clientes/check_dni.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Response.php';

AuthMiddleware::requireAuth();

try {
    $dni = $_GET['dni'] ?? '';
    $id = $_GET['id'] ?? null;

    if (empty($dni)) {
        header('Content-Type: application/json');
        echo json_encode(['exists' => false]);
        exit;
    }

    $db = getDB();

    // Verificar si existe el DNI (excluyendo el ID actual si es edición)
    if ($id) {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :dni AND id != :id");
        $stmt->execute(['dni' => $dni, 'id' => $id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM clientes WHERE numero_documento = :dni");
        $stmt->execute(['dni' => $dni]);
    }

    $exists = $stmt->fetch() !== false;

    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);

} catch (Exception $e) {
    error_log("Error en check_dni.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['exists' => false]);
}
