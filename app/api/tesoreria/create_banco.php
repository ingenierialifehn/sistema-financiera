<?php
require_once '../../config/database.php';

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('tesoreria.crear');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['nombre_banco']) || empty($data['numero_cuenta'])) {
        throw new Exception('Nombre y número de cuenta son obligatorios');
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO bancos (nombre_banco, numero_cuenta, tipo_cuenta, moneda, saldo_actual) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['nombre_banco'],
        $data['numero_cuenta'],
        $data['tipo_cuenta'] ?? 'Ahorro',
        $data['moneda'] ?? 'HNL',
        $data['saldo_inicial'] ?? 0.00
    ]);

    echo json_encode(['success' => true, 'message' => 'Banco registrado correctamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
