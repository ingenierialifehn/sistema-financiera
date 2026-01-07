<?php
/**
 * API: Obtener saldo del sistema (saldo_efectivo de la agencia)
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

    // Obtener saldo de cajas_agencias (saldo_caja_operativa es el de la caja diaria)
    $stmt = $db->prepare("SELECT saldo_caja_operativa as saldo, saldo_efectivo as saldo_boveda FROM cajas_agencias WHERE id_agencia = ?");
    $stmt->execute([$idAgencia]);
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($caja) {
        Response::success([
            'saldo' => floatval($caja['saldo']),
            'saldo_boveda' => floatval($caja['saldo_boveda'])
        ]);
    } else {
        // Si no existe registro, es 0.00
        Response::success([
            'saldo' => 0.00
        ]);
    }

} catch (Exception $e) {
    error_log("Error en get_saldo_sistema.php: " . $e->getMessage());
    Response::serverError('Error al obtener saldo del sistema');
}
