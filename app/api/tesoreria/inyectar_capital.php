<?php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $bancoId = $data['banco_id'];
    $monto = $data['monto'];
    $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? 1); // Fallback to 1 (Admin)

    if ($monto <= 0)
        throw new Exception("El monto debe ser mayor a 0");

    $db = getDB();
    $db->beginTransaction();

    // Get current balance
    $stmt = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");
    $stmt->execute([$bancoId]);
    $banco = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$banco)
        throw new Exception("Banco no encontrado");

    $saldoAnterior = $banco['saldo_actual'];
    $saldoNuevo = $saldoAnterior + $monto;

    // Update Banco
    $upd = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
    $upd->execute([$saldoNuevo, $bancoId]);

    // Log Movement
    $log = $db->prepare("INSERT INTO movimientos_bancarios (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por) VALUES (?, 'ingreso', ?, ?, ?, ?, ?)");
    $log->execute([$bancoId, $monto, $saldoAnterior, $saldoNuevo, 'Inyección de Capital', $userId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Capital inyectado exitosamente']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction())
        $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
