<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

// Ensure JSON response
header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment if auth is needed for this check

    if (!isset($_GET['cliente_id'])) {
        throw new Exception("ID de cliente requerido");
    }

    $clienteId = intval($_GET['cliente_id']);
    $db = getDB();

    $stmt = $db->prepare("SELECT COUNT(*) as active_count FROM prestamos WHERE id_cliente = ? AND estado = 'Activo'");
    $stmt->execute([$clienteId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $hasActiveLoan = $result['active_count'] > 0;

    echo json_encode([
        'success' => true,
        'has_active_loan' => $hasActiveLoan
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>