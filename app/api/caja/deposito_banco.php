<?php
/**
 * API: Devolución de dinero de Caja a Banco (Depósito)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
// Permiso de caja o operaciones
Auth::requirePermission('caja');

$db = getDB();
$user = Auth::getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    $bancoId = $data['banco_id'] ?? null;
    $monto = floatval($data['monto'] ?? 0);
    $referencia = $data['referencia'] ?? ''; // Numero de transferencia/deposito
    $observaciones = $data['observaciones'] ?? '';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];
    $userId = $user['id_usuario'];

    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    if (!$bancoId) {
        Response::error('Debe seleccionar un banco', 400);
    }

    if (!$referencia) {
        Response::error('El número de depósito/transferencia es requerido', 400);
    }

    if ($monto <= 0) {
        Response::error('El monto debe ser mayor a 0', 400);
    }

    $db->beginTransaction();

    // 1. Verificar saldo en Caja (cajas_agencias.saldo_caja_operativa)
    $stmtCheck = $db->prepare("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ? FOR UPDATE");
    $stmtCheck->execute([$idAgencia]);
    $caja = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$caja) {
        throw new Exception("No se encontró registro de caja para la agencia.");
    }

    $saldoActualCaja = floatval($caja['saldo_caja_operativa']);

    if ($monto > $saldoActualCaja) {
        $db->rollBack();
        Response::error('Fondos insuficientes en Caja Operativa. Saldo actual: ' . number_format($saldoActualCaja, 2), 400);
    }

    // 2. Verificar y Bloquear Banco
    $stmtBanco = $db->prepare("SELECT saldo_actual, nombre_banco FROM bancos WHERE id = ? FOR UPDATE");
    $stmtBanco->execute([$bancoId]);
    $banco = $stmtBanco->fetch(PDO::FETCH_ASSOC);

    if (!$banco) {
        throw new Exception("Banco no encontrado.");
    }

    $saldoAnteriorBanco = $banco['saldo_actual'];

    // 3. Restar de Caja
    $stmtUpdateCaja = $db->prepare("
        UPDATE cajas_agencias 
        SET saldo_caja_operativa = saldo_caja_operativa - :monto,
            ultima_actualizacion = NOW()
        WHERE id_agencia = :id_agencia
    ");

    $stmtUpdateCaja->execute([
        ':monto' => $monto,
        ':id_agencia' => $idAgencia
    ]);

    // 4. Sumar a Banco
    $saldoNuevoBanco = $saldoAnteriorBanco + $monto;
    $stmtUpdateBanco = $db->prepare("UPDATE bancos SET saldo_actual = ? WHERE id = ?");
    $stmtUpdateBanco->execute([$saldoNuevoBanco, $bancoId]);

    // 5. Registrar Movimiento Bancario
    $descBanco = "Depósito a cuenta. Ref: " . $referencia . ". " . $observaciones;
    $stmtMovBancoSafe = $db->prepare("
        INSERT INTO movimientos_bancarios 
        (banco_id, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, realizado_por) 
        VALUES (?, 'ingreso', ?, ?, ?, ?, ?)
    ");
    $stmtMovBancoSafe->execute([
        $bancoId,
        $monto,
        $saldoAnteriorBanco,
        $saldoNuevoBanco,
        $descBanco,
        $userId
    ]);


    // 6. Registrar movimiento interno de agencia
    $stmtMovimiento = $db->prepare("
        INSERT INTO movimientos_internos_agencia 
        (id_agencia, id_usuario_operador, tipo_movimiento, monto, fecha_movimiento, observaciones)
        VALUES (:id_agencia, :id_usuario, 'Caja a Banco', :monto, NOW(), :observaciones)
    ");

    $obsCompleta = "Depósito a " . $banco['nombre_banco'] . " Ref: " . $referencia . ". " . $observaciones;

    $stmtMovimiento->execute([
        ':id_agencia' => $idAgencia,
        ':id_usuario' => $userId,
        ':monto' => $monto,
        ':observaciones' => $obsCompleta
    ]);

    $db->commit();

    Response::success([
        'message' => 'Depósito a banco registrado exitosamente',
        'monto' => $monto,
        'nuevo_saldo_caja' => $saldoActualCaja - $monto
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en deposito_banco.php: " . $e->getMessage());
    Response::serverError('Error al procesar el depósito: ' . $e->getMessage());
}
