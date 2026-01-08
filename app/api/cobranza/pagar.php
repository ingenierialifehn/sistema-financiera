<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    // Auth::requireLogin();
    $userId = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1;

    $input = json_decode(file_get_contents('php://input'), true);
    $cuotaId = $input['cuota_id'] ?? null;

    if (!$cuotaId) {
        throw new Exception("ID de cuota requerido");
    }

    $db = getDB();
    $db->beginTransaction();

    // 1. Obtener datos de la cuota
    $stmt = $db->prepare("SELECT c.*, p.id as prestamo_id, cl.id_agencia, cl.nombre_completo 
                          FROM cuotas c
                          JOIN prestamos p ON c.prestamo_id = p.id
                          JOIN clientes cl ON p.id_cliente = cl.id
                          WHERE c.id = ? FOR UPDATE");
    $stmt->execute([$cuotaId]);
    $cuota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuota) {
        throw new Exception("Cuota no encontrada");
    }

    if ($cuota['estado'] === 'pagada') {
        throw new Exception("Esta cuota ya está pagada");
    }

    $monto = floatval($cuota['monto_cuota']);
    $agenciaId = $cuota['id_agencia'];

    // 2. Actualizar Cuota
    $stmtUpdate = $db->prepare("UPDATE cuotas SET 
                                estado = 'pagada',
                                fecha_pago = CURDATE(),
                                fecha_pago_real = NOW(),
                                usuario_cobro_id = ?,
                                monto_pagado = ?
                                WHERE id = ?");
    $stmtUpdate->execute([$userId, $monto, $cuotaId]);

    // 3. Actualizar Caja Agencia (Ingreso)
    $stmtCaja = $db->prepare("UPDATE cajas_agencias 
                              SET saldo_caja_operativa = saldo_caja_operativa + ? 
                              WHERE id_agencia = ?");
    $stmtCaja->execute([$monto, $agenciaId]);

    // 4. Registrar Movimiento Agencia
    $stmtLog = $db->prepare("INSERT INTO movimientos_internos_agencia 
                             (id_agencia, id_usuario_operador, tipo_movimiento, monto, observaciones, fecha_movimiento)
                             VALUES (?, ?, 'Ingreso por Cobro', ?, ?, NOW())");
    $stmtLog->execute([
        $agenciaId,
        $userId,
        $monto,
        "Cobro Cuota #{$cuota['numero_cuota']} - Préstamo #{$cuota['prestamo_id']} - {$cuota['nombre_completo']}"
    ]);

    // 5. Verificar si el préstamo terminó (todas pagadas)
    // Contar cuotas pendientes
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM cuotas WHERE prestamo_id = ? AND estado != 'pagada'");
    $stmtCheck->execute([$cuota['prestamo_id']]);
    $pendientes = $stmtCheck->fetchColumn();

    if ($pendientes == 0) {
        // Actualizar préstamo a Finalizado
        $stmtPrestamo = $db->prepare("UPDATE prestamos SET estado = 'Finalizado', updated_at = NOW() WHERE id = ?");
        $stmtPrestamo->execute([$cuota['prestamo_id']]);
        $msg = "Cuota pagada con éxito. Préstamo Finalizado.";
    } else {
        $msg = "Pago registrado correctamente.";
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $msg
    ]);

} catch (Exception $e) {
    if (isset($db))
        $db->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>