<?php
/**
 * API: Apertura de caja
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

// Verificar permisos
if (!Auth::hasPermission('caja.crear') && !Auth::hasPermission('caja.editar') && !Auth::hasPermission('caja')) {
    Response::forbidden('No tiene permisos para abrir caja');
}

$db = getDB();
$user = Auth::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $saldoAperturaSistema = $data['saldo_apertura_sistema'] ?? null;
    $saldoAperturaFisico = $data['saldo_apertura_fisico'] ?? null;
    $observaciones = $data['observaciones'] ?? '';
    $idUsuario = $user['id_usuario'];
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    // Validaciones
    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    if ($saldoAperturaSistema === null || $saldoAperturaFisico === null) {
        Response::error('Saldos de apertura son requeridos', 400);
    }

    // Verificar que no haya una caja abierta
    $stmt = $db->prepare("
        SELECT id_control 
        FROM control_caja_diaria 
        WHERE id_agencia = ? 
        AND fecha_dia = CURDATE() 
        AND estado = 'Abierto'
    ");
    $stmt->execute([$idAgencia]);

    if ($stmt->fetch()) {
        Response::error('Ya existe una caja abierta para hoy', 400);
    }

    $montoRetiroBoveda = $data['monto_retiro_boveda'] ?? 0;

    // Iniciar Transacción
    $db->beginTransaction();

    // 1. Si hay retiro de bóveda, procesarlo primero
    if ($montoRetiroBoveda > 0) {
        // Verificar saldo bóveda y bloquear fila
        $stmtCaja = $db->prepare("SELECT saldo_efectivo, saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ? FOR UPDATE");
        $stmtCaja->execute([$idAgencia]);
        $caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

        if (!$caja || $caja['saldo_efectivo'] < $montoRetiroBoveda) {
            $db->rollBack();
            Response::error("Saldo insuficiente en bóveda para el retiro inicial (Disponible: L. " . number_format($caja['saldo_efectivo'] ?? 0, 2) . ")", 400);
        }

        // Actualizar saldos
        $nuevoSaldoBoveda = $caja['saldo_efectivo'] - $montoRetiroBoveda;
        $nuevoSaldoOperativo = $caja['saldo_caja_operativa'] + $montoRetiroBoveda;

        $stmtUpd = $db->prepare("UPDATE cajas_agencias SET saldo_efectivo = ?, saldo_caja_operativa = ?, ultima_actualizacion = NOW() WHERE id_agencia = ?");
        $stmtUpd->execute([$nuevoSaldoBoveda, $nuevoSaldoOperativo, $idAgencia]);

        // Registrar movimiento (Intentamos hacerlo en tabla de auditoría si existe, si no, al menos queda en el log del cierre/apertura)
        // Asumimos movimientos_boveda o similar. Si no, al menos actualizar saldo_apertura_sistema.

        // Ajustamos el saldo sistema de apertura para que refleje el dinero que YA entró
        // Si saldoAperturaSistema venia en 0, ahora es 0 + retiro.
        $saldoAperturaSistema += $montoRetiroBoveda;

        // Agregar observación automática
        $observaciones .= " [Retiro Inicial Bóveda: L. " . number_format($montoRetiroBoveda, 2) . "]";
    }

    // Insertar apertura
    $stmt = $db->prepare("
        INSERT INTO control_caja_diaria (
            id_agencia,
            id_usuario_apertura,
            fecha_dia,
            saldo_apertura_sistema,
            saldo_apertura_fisico,
            observaciones,
            estado
        ) VALUES (?, ?, CURDATE(), ?, ?, ?, 'Abierto')
    ");

    $stmt->execute([
        $idAgencia,
        $idUsuario,
        $saldoAperturaSistema,
        $saldoAperturaFisico, // Este ya incluye el dinero físico contado (que incluye lo de la bóveda)
        $observaciones
    ]);

    $idControl = $db->lastInsertId();
    $db->commit();

    Response::success([
        'message' => 'Caja abierta exitosamente',
        'id_control' => $idControl
    ]);

} catch (Exception $e) {
    error_log("Error en apertura.php: " . $e->getMessage());
    Response::serverError('Error al abrir caja');
}
