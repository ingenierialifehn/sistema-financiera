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

        $margenTop = $data['margen_top'] ?? 20;
        $margenBottom = $data['margen_bottom'] ?? 20;
        $margenLeft = $data['margen_left'] ?? 25;
        $margenRight = $data['margen_right'] ?? 25;
        $orientacion = $data['orientacion'] ?? 'portrait';
        $logoAncho = $data['logo_ancho'] ?? 150;
        $tamanoPapel = $data['tamano_papel'] ?? 'carta';

        $stmt = $db->prepare("UPDATE plantillas_documentos SET contenido = ?, margen_top = ?, margen_bottom = ?, margen_left = ?, margen_right = ?, orientacion = ?, logo_ancho = ?, tamano_papel = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([
            $data['contenido'],
            $margenTop,
            $margenBottom,
            $margenLeft,
            $margenRight,
            $orientacion,
            $logoAncho,
            $tamanoPapel,
            $data['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Plantilla guardada correctamente']);
    } else {
        throw new Exception("Método no permitido");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>