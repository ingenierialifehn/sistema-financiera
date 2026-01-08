<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception("Sesión no iniciada");
    }

    $userId = $_SESSION['id_usuario'];
    $data = json_decode(file_get_contents("php://input"), true);

    $prestamoId = $data['prestamo_id'] ?? null;
    $assignAll = $data['assign_all'] ?? false;

    $db = getDB();

    if ($assignAll) {
        // Asignar TODO lo activo que no sea mio
        $stmt = $db->prepare("UPDATE prestamos SET asesor_creditos_id = ?, oficial_desembolsos_id = ? 
                              WHERE estado = 'Activo' AND (asesor_creditos_id != ? OR asesor_creditos_id IS NULL)");
        $stmt->execute([$userId, $userId, $userId]);
        $count = $stmt->rowCount();
        echo json_encode(['success' => true, 'message' => "Se han asignado $count préstamos a su cartera."]);

    } elseif ($prestamoId) {
        $stmt = $db->prepare("UPDATE prestamos SET asesor_creditos_id = ?, oficial_desembolsos_id = ? WHERE id = ?");
        $stmt->execute([$userId, $userId, $prestamoId]);
        echo json_encode(['success' => true, 'message' => "Préstamo #$prestamoId asignado correctamente."]);

    } else {
        throw new Exception("Datos incompletos");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>