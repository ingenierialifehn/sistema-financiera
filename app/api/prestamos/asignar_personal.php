<?php
/**
 * API: Asignar personal al préstamo (Asesor de Créditos y Oficial de Desembolsos)
 */

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

    if (empty($data['prestamo_id'])) {
        throw new Exception("ID de préstamo es obligatorio");
    }

    $prestamoId = intval($data['prestamo_id']);
    $asesorCreditosId = !empty($data['asesor_creditos_id']) ? intval($data['asesor_creditos_id']) : null;
    $oficialDesembolsosId = !empty($data['oficial_desembolsos_id']) ? intval($data['oficial_desembolsos_id']) : null;

    $db = getDB();

    // Verify loan exists
    $stmt = $db->prepare("SELECT id, estado FROM prestamos WHERE id = ?");
    $stmt->execute([$prestamoId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        throw new Exception("Préstamo no encontrado");
    }

    // Update assignments
    $sql = "UPDATE prestamos SET 
            asesor_creditos_id = ?,
            oficial_desembolsos_id = ?,
            updated_at = NOW()
            WHERE id = ?";

    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute([$asesorCreditosId, $oficialDesembolsosId, $prestamoId]);

    echo json_encode([
        'success' => true,
        'message' => 'Personal asignado correctamente',
        'data' => [
            'asesor_creditos_id' => $asesorCreditosId,
            'oficial_desembolsos_id' => $oficialDesembolsosId
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>