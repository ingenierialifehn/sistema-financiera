<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['contenido'])) {
            throw new Exception("Datos incompletos");
        }

        $db = getDB();
        $stmt = $db->prepare("UPDATE plantillas_documentos SET contenido = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['contenido'], $data['id']]);

        echo json_encode(['success' => true, 'message' => 'Plantilla guardada correctamente']);
    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>