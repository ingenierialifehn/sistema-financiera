<?php
/**
 * API: Cierre de caja
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

// Verificar permisos
if (!Auth::hasPermission('caja.crear') && !Auth::hasPermission('caja.editar') && !Auth::hasPermission('caja')) {
    Response::forbidden('No tiene permisos para cerrar caja');
}

$db = getDB();
$user = Auth::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $idControl = $data['id_control'] ?? null;
    $saldoCierreSistema = $data['saldo_cierre_sistema'] ?? null;
    $saldoCierreFisico = $data['saldo_cierre_fisico'] ?? null;
    $diferenciaCierre = $data['diferencia_cierre'] ?? 0;
    $observaciones = $data['observaciones'] ?? '';
    $idUsuario = $user['id_usuario'];

    // Validaciones
    if (!$idControl) {
        Response::error('ID de control es requerido', 400);
    }

    if ($saldoCierreSistema === null || $saldoCierreFisico === null) {
        Response::error('Saldos de cierre son requeridos', 400);
    }

    // Verificar que la caja esté abierta
    $stmt = $db->prepare("
        SELECT id_control, estado 
        FROM control_caja_diaria 
        WHERE id_control = ? AND estado = 'Abierto'
    ");
    $stmt->execute([$idControl]);
    $caja = $stmt->fetch();

    if (!$caja) {
        Response::error('La caja no está abierta o no existe', 400);
    }

    // Validar que la caja esté en cero (todo devuelto a bóveda)
    // 1. Obtener agencia del usuario
    // Ya tenemos $user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idAgencia = $_SESSION['id_agencia'] ?? $user['id_agencia'];

    // 2. Consultar saldo en cajas_agencias
    $stmtSaldo = $db->prepare("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = ?");
    $stmtSaldo->execute([$idAgencia]);
    $saldoAgencia = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if ($saldoAgencia) {
        $saldoActual = floatval($saldoAgencia['saldo_caja_operativa']);
        if ($saldoActual > 0.01) {
            Response::error("No se puede cerrar caja con fondos (L. " . number_format($saldoActual, 2) . "). Debe devolver todo el dinero a Bóveda.", 400);
        }
    }

    // Actualizar cierre
    $stmt = $db->prepare("
        UPDATE control_caja_diaria 
        SET 
            id_usuario_cierre = ?,
            hora_cierre = NOW(),
            saldo_cierre_sistema = ?,
            saldo_cierre_fisico = ?,
            diferencia_cierre = ?,
            observaciones = CONCAT(IFNULL(observaciones, ''), '\n--- CIERRE ---\n', ?),
            estado = 'Cerrado'
        WHERE id_control = ?
    ");

    $stmt->execute([
        $idUsuario,
        $saldoCierreSistema,
        $saldoCierreFisico,
        $diferenciaCierre,
        $observaciones,
        $idControl
    ]);

    Response::success([
        'message' => 'Caja cerrada exitosamente',
        'diferencia' => $diferenciaCierre
    ]);

} catch (Exception $e) {
    error_log("Error en cierre.php: " . $e->getMessage());
    Response::serverError('Error al cerrar caja');
}
