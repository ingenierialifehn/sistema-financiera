<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $db = getDB();
    // Fetch active users.
    $stmt = $db->query("SELECT id_usuario, username, saldo_caja_virtual, rol FROM usuarios WHERE estado = 'Activo'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
