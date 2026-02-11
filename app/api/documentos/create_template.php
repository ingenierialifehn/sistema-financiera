<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['nombre']) || empty($data['tipo'])) {
            throw new Exception("Datos incompletos");
        }

        $db = getDB();

        // Create with default content
        $defaultContent = '<h3 style="text-align: center;">' . strtoupper($data['nombre']) . '</h3><p>Contenido de la plantilla...</p>';

        $stmt = $db->prepare("INSERT INTO plantillas_documentos (nombre, tipo, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$data['nombre'], $data['tipo'], $defaultContent]);

        $newId = $db->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Plantilla creada correctamente', 'id' => $newId]);
    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>