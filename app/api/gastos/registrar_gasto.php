<?php
require_once '../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

// Ideally, check for specific permission. 
// Assuming 'gastos' permission or just 'admin' role for now as it's a new module.
// For now, allow if user is logged in and has 'admin' role or similar.
// check if user is admin or gerente
$user = Auth::checkSession();
if (!$user || !in_array($user['rol_nombre'], ['Administrador', 'Gerente', 'Supervisor'])) {
    Response::forbidden('No tiene permisos para registrar gastos operativos.');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $bancoId = $data['banco_id'] ?? null;
    $agenciaId = $data['agencia_id'] ?? null;
    $categoria = $data['categoria'] ?? null;
    $monto = floatval($data['monto'] ?? 0);
    $descripcion = $data['descripcion'] ?? '';
    $userId = $user['id_usuario'];

    if (!$bancoId || !$agenciaId || !$categoria || $monto <= 0) {
        throw new Exception("Todos los campos son obligatorios y el monto debe ser mayor a 0.");
    }

    $db = getDB();
    $db->beginTransaction();

    // 1. Get Bank Balance & Lock Row
    $stmt = $db->prepare("SELECT saldo_actual FROM bancos WHERE id = ? FOR UPDATE");
    $stmt->execute([$bancoId]);
    $banco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$banco) {
        throw new Exception("Banco no encontrado");
    }

    $saldoAnterior = floatval($banco['saldo_actual']);

    if ($saldoAnterior < $monto) {
        throw new Exception("Saldo insuficiente en la cuenta bancaria seleccionada.");
    }

    $saldoNuevo = $saldoAnterior - $monto;

    // 2. Update Banco
    $upd = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
    $upd->execute([$saldoNuevo, $bancoId]);

    // 3. Insert Movimiento Bancario (Egreso)
    $stmtBan = $db->prepare("INSERT INTO movimientos_bancarios 
        (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por) 
        VALUES (?, 'egreso', ?, ?, ?, ?, ?)");
    // Use category and description for the bank description
    $fullDesc = "Gasto Operativo ($categoria): " . $descripcion;
    $stmtBan->execute([$bancoId, $monto, $saldoAnterior, $saldoNuevo, $fullDesc, $userId]);

    // 4. Insert Movimiento Interno Agencia
    // Note: This does NOT update the agency's cash box (cajas_agencias) as the payment was made via Bank, not Cash.
    // It serves as a record/categorization of the expense for the agency.
    $stmtMov = $db->prepare("INSERT INTO movimientos_internos_agencia 
        (id_agencia, id_usuario_operador, tipo_movimiento, monto, fecha_movimiento, observaciones)
        VALUES (?, ?, ?, ?, NOW(), ?)");

    // Using $categoria as 'tipo_movimiento' as requested
    $stmtMov->execute([$agenciaId, $userId, $categoria, $monto, "Pago via Banco: " . $descripcion]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Gasto registrado exitosamente']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
