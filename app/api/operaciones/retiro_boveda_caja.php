<?php
/**
 * API: Retiro de Bóveda a Caja (Operaciones)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
// Ajustar permiso según necesidad, usaremos 'operaciones' genérico o 'crear'
Auth::requirePermission('operaciones');

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

    // 1. Verificar saldo en bóveda (cajas_agencias.saldo_efectivo)
    // Se asume que existe la tabla cajas_agencias vinculada a la agencia
    $stmtCheck = $db->prepare("SELECT saldo_efectivo FROM cajas_agencias WHERE id_agencia = ? FOR UPDATE");
    $stmtCheck->execute([$idAgencia]);
    $caja = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$caja) {
        // Intentar crear el registro si no existe (opcional, pero seguro) o dar error
        // Asumimos que debe existir. Si no, es un error crítico de configuración.
        throw new Exception("No se encontró registro de caja para la agencia.");
    }

    $saldoActualBoveda = floatval($caja['saldo_efectivo']);

    if ($monto > $saldoActualBoveda) {
        $db->rollBack();
        Response::error('Fondos insuficientes en Bóveda. Saldo actual: ' . number_format($saldoActualBoveda, 2), 400);
    }

    // 2. Restar de Bóveda y Sumar a Caja Operativa
    $stmtUpdate = $db->prepare("
        UPDATE cajas_agencias 
        SET saldo_efectivo = saldo_efectivo - :monto_resta,
            saldo_caja_operativa = saldo_caja_operativa + :monto_suma,
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
        VALUES (:id_agencia, :id_usuario, 'Boveda a Caja', :monto, NOW(), :descripcion)
    ");

    $stmtMovimiento->execute([
        ':id_agencia' => $idAgencia,
        ':id_usuario' => $user['id_usuario'], // Asumiendo que el usuario logueado hace la operación
        ':monto' => $monto,
        ':descripcion' => "Retiro de Bóveda a Caja para operaciones. " . $observaciones
    ]);

    $db->commit();

    Response::success([
        'message' => 'Retiro de bóveda a caja realizado exitosamente',
        'monto' => $monto,
        'nuevo_saldo_boveda' => $saldoActualBoveda - $monto
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en retiro_boveda_caja.php: " . $e->getMessage());
    Response::serverError('Error al procesar el retiro: ' . $e->getMessage());
}
