<?php
/**
 * API: Obtener estado actual de la caja
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('caja');

$db = getDB();
$user = Auth::getCurrentUser();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

try {
    if (!$idAgencia) {
        Response::error('Usuario no tiene agencia asignada', 400);
    }

    // 1. Buscar si hay ALGUNA caja ABIERTA (Prioridad: Bloquear nueva apertura si hay una pendiente)
    $stmt = $db->prepare("
        SELECT 
            c.*,
            COALESCE(ca.saldo_efectivo, 0) as saldo_boveda,
            COALESCE(ca.saldo_caja_operativa, 0) as saldo_caja
        FROM control_caja_diaria c
        LEFT JOIN cajas_agencias ca ON c.id_agencia = ca.id_agencia
        WHERE c.id_agencia = ? 
        AND c.estado = 'Abierto'
        ORDER BY c.fecha_dia ASC -- Traer la más antigua abierta primero (o la única)
        LIMIT 1
    ");
    $stmt->execute([$idAgencia]);
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Si no hay abierta, buscar si hubo una CERRADA hoy (para mostrar info del día)
    if (!$caja) {
        $stmtCerrada = $db->prepare("
            SELECT 
                c.*,
                COALESCE(ca.saldo_efectivo, 0) as saldo_boveda,
                COALESCE(ca.saldo_caja_operativa, 0) as saldo_caja
            FROM control_caja_diaria c
            LEFT JOIN cajas_agencias ca ON c.id_agencia = ca.id_agencia
            WHERE c.id_agencia = ? 
            AND c.fecha_dia = CURDATE()
            AND c.estado = 'Cerrado'
            ORDER BY c.id_control DESC
            LIMIT 1
        ");
        $stmtCerrada->execute([$idAgencia]);
        $caja = $stmtCerrada->fetch(PDO::FETCH_ASSOC);
    }

    if ($caja) {
        Response::success($caja);
    } else {
        // No hay caja abierta para hoy, pero devolvemos saldos generales
        $stmtSaldos = $db->prepare("SELECT saldo_efectivo as saldo_boveda, saldo_caja_operativa as saldo_caja FROM cajas_agencias WHERE id_agencia = ?");
        $stmtSaldos->execute([$idAgencia]);
        $saldos = $stmtSaldos->fetch(PDO::FETCH_ASSOC);

        Response::success([
            'estado' => 'Cerrado',
            'mensaje' => 'No hay caja abierta para hoy',
            'saldo_boveda' => $saldos['saldo_boveda'] ?? 0,
            'saldo_caja' => $saldos['saldo_caja'] ?? 0
        ]);
    }

} catch (Exception $e) {
    error_log("Error en get_estado.php: " . $e->getMessage());
    Response::serverError('Error al obtener estado de caja');
}
