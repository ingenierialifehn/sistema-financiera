<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data)
        $data = $_POST;

    if (empty($data['prestamo_id']) || empty($data['accion'])) {
        throw new Exception("ID de préstamo y acción son obligatorios");
    }

    $prestamoId = intval($data['prestamo_id']);
    $accion = strtolower($data['accion']); // 'autorizar' o 'rechazar'
    $comentario = $data['comentario'] ?? '';

    $db = getDB();
    $db->beginTransaction();

    try {
        $nuevoEstado = '';
        if ($accion === 'autorizar') {
            $nuevoEstado = 'verificado';
        } elseif ($accion === 'rechazar') {
            $nuevoEstado = 'Rechazado';
        } else {
            throw new Exception("Acción no válida. Use 'autorizar' o 'rechazar'.");
        }

        // Add 'Rechazado en Ruta' logic if needed, but for verification stage usually it goes to 'Rechazado' directly 
        // as no money is out yet (usually).
        // However, if we reuse update_status logic it is better, but here we just update specific fields.
        // Let's stick to simple update for now as Verificacion de Campo implies pre-disbursement.

        $stmt = $db->prepare("UPDATE prestamos SET 
            estado = ?, 
            comentario_verificacion = ?, 
            updated_at = NOW() 
            WHERE id = ?");

        $stmt->execute([$nuevoEstado, $comentario, $prestamoId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Préstamo " . ($accion === 'autorizar' ? 'verificado' : 'rechazado') . " exitosamente."
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
