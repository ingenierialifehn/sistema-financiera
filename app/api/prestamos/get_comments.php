<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    AuthMiddleware::requireAuth();

    if (!isset($_GET['id'])) {
        throw new Exception("ID de préstamo requerido");
    }

    $prestamoId = intval($_GET['id']);
    $db = getDB();

    $sql = "SELECT c.*, u.nombre_completo as usuario_nombre, u.rol as usuario_rol 
            FROM prestamos_comentarios c
            JOIN usuarios u ON c.usuario_id = u.id_usuario
            WHERE c.prestamo_id = ?
            ORDER BY c.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$prestamoId]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $comentarios
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>