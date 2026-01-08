<?php
/**
 * API: Obtener cuotas de un préstamo
 * GET /app/api/prestamos/cuotas.php?prestamo_id=1
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    if (!isset($_GET['prestamo_id']) || empty($_GET['prestamo_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de préstamo es requerido']);
        exit;
    }

    $prestamoId = intval($_GET['prestamo_id']);
    $db = getDB();

    // Verificar que el préstamo existe
    $stmt = $db->prepare("SELECT id FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Préstamo no encontrado']);
        exit;
    }

    // Obtener cuotas
    $stmt = $db->prepare("
        SELECT 
            id,
            prestamo_id,
            numero_cuota,
            monto_cuota,
            fecha_vencimiento,
            estado
        FROM cuotas
        WHERE prestamo_id = ?
        ORDER BY numero_cuota ASC
    ");
    $stmt->execute([$prestamoId]);
    $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Cuotas obtenidas exitosamente',
        'data' => [
            'cuotas' => $cuotas
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en prestamos/cuotas.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener cuotas: ' . $e->getMessage()
    ]);
}
?>