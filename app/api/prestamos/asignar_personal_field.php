<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment in production

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data)
        $data = $_POST;

    if (empty($data['prestamo_id']) || empty($data['field']) || empty($data['value'])) {
        throw new Exception("Datos incompletos");
    }

    $prestamoId = intval($data['prestamo_id']);
    $field = $data['field'];
    $value = intval($data['value']);

    // Validate field name to prevent SQL Injection
    $allowedFields = ['asesor_creditos_id', 'oficial_desembolsos_id'];
    if (!in_array($field, $allowedFields)) {
        throw new Exception("Campo no válido");
    }

    $db = getDB();

    // Update the specific field
    $stmt = $db->prepare("UPDATE prestamos SET $field = ? WHERE id = ?");
    $stmt->execute([$value, $prestamoId]);

    echo json_encode([
        'success' => true,
        'message' => 'Actualizado correctamente'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
