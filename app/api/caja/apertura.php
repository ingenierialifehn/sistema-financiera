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
        $saldoAperturaFisico,
        $observaciones
    ]);

    Response::success([
        'message' => 'Caja abierta exitosamente',
        'id_control' => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log("Error en apertura.php: " . $e->getMessage());
    Response::serverError('Error al abrir caja');
}
