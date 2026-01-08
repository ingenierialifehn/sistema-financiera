<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    $user = AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input)
        $input = $_POST;

    if (empty($input['prestamo_id']) || empty($input['comentario'])) {
        throw new Exception("Prestamo ID y comentario son requeridos");
    }

    $prestamoId = intval($input['prestamo_id']);
    $comentario = trim($input['comentario']);
    $usuarioId = $user['id_usuario'];

    // Optional: Capture current stage if useful
    $db = getDB();
    $stmtLoan = $db->prepare("SELECT estado FROM prestamos WHERE id = ?");
    $stmtLoan->execute([$prestamoId]);
    $loanState = $stmtLoan->fetchColumn();

    $sql = "INSERT INTO prestamos_comentarios (prestamo_id, usuario_id, comentario, etapa_flujo) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$prestamoId, $usuarioId, $comentario, $loanState]);

    echo json_encode([
        'success' => true,
        'message' => 'Comentario agregado correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>