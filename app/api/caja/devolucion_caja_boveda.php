<?php
/**
 * API: Devolución de dinero de Caja a Bóveda
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
    $monto = floatval($data['monto'] ?? 0);
    $observaciones = $data['observaciones'] ?? '';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
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

    // 2. Restar de Caja y Sumar a Bóveda
    // Nota: Usamos nombres de parámetros distintos para evitar conflictos si emulation mode es false
    $stmtUpdate = $db->prepare("
        UPDATE cajas_agencias 
        SET saldo_caja_operativa = saldo_caja_operativa - :monto_resta,
            saldo_efectivo = saldo_efectivo + :monto_suma,
            ultima_actualizacion = NOW()
        WHERE id_agencia = :id_agencia
    ");

    $stmtUpdate->execute([
        ':monto_resta' => $monto,
        ':monto_suma' => $monto,
        ':id_agencia' => $idAgencia
    ]);

    // 3. Registrar movimiento interno
    $stmtMovimiento = $db->prepare("
        INSERT INTO movimientos_internos_agencia 
        (id_agencia, id_usuario_operador, tipo_movimiento, monto, fecha_movimiento, observaciones)
        VALUES (:id_agencia, :id_usuario, 'Caja a Boveda', :monto, NOW(), :observaciones)
    ");

    $stmtMovimiento->execute([
        ':id_agencia' => $idAgencia,
        ':id_usuario' => $user['id_usuario'],
        ':monto' => $monto,
        ':observaciones' => "Devolución de Caja a Bóveda (Cierre). " . $observaciones
    ]);

    $db->commit();

    Response::success([
        'message' => 'Devolución a bóveda realizada exitosamente',
        'monto' => $monto,
        'nuevo_saldo_caja' => $saldoActualCaja - $monto
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en devolucion_caja_boveda.php: " . $e->getMessage());
    Response::serverError('Error al procesar la devolución: ' . $e->getMessage());
}
