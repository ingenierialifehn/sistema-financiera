<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment if needed

    $db = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Get specific template
            $stmt = $db->prepare("SELECT * FROM plantillas_documentos WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $template]);
        } else {
            // List all
            $stmt = $db->query("SELECT id, nombre, tipo, updated_at FROM plantillas_documentos WHERE estado = 'activo' ORDER BY tipo, nombre");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>