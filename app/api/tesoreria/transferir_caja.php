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
    $usuarioDestinoId = $data['usuario_destino_id'];
    $monto = $data['monto'];
    $userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? 1);

    if ($monto <= 0)
        throw new Exception("El monto debe ser mayor a 0");

    $db = getDB();
    $db->beginTransaction();

    // 1. Debit Bank
    $stmt = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");
    $stmt->execute([$bancoId]);
    $banco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$banco)
        throw new Exception("Banco no encontrado");
    if ($banco['saldo_actual'] < $monto)
        throw new Exception("Saldo insuficiente en banco");

    $saldoAnteriorBanco = $banco['saldo_actual'];
    $saldoNuevoBanco = $saldoAnteriorBanco - $monto;

    $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?")->execute([$saldoNuevoBanco, $bancoId]);

    // 2. Credit User
    $stmtUser = $db->prepare("SELECT saldo_caja_virtual FROM usuarios WHERE id_usuario = ? FOR UPDATE");
    $stmtUser->execute([$usuarioDestinoId]);
    $userDest = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userDest)
        throw new Exception("Usuario destino no encontrado");

    $saldoNuevoUser = $userDest['saldo_caja_virtual'] + $monto;
    $db->prepare("UPDATE usuarios SET saldo_caja_virtual = ? WHERE id_usuario = ?")->execute([$saldoNuevoUser, $usuarioDestinoId]);

    // 3. Log Bank Movement
    $log = $db->prepare("INSERT INTO movimientos_bancarios (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por, entidad_destino_tipo, entidad_destino_id) VALUES (?, 'traspaso_caja', ?, ?, ?, ?, ?, 'usuario', ?)");
    $log->execute([$bancoId, $monto, $saldoAnteriorBanco, $saldoNuevoBanco, 'Traspaso a Caja', $userId, $usuarioDestinoId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Transferencia realizada exitosamente']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction())
        $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
