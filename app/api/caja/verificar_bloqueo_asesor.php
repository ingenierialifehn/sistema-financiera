<?php
/**
 * API para verificar si un asesor está bloqueado para cobros
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();

header('Content-Type: application/json');

$db = getDB();
$user = AuthMiddleware::getCurrentUser();
$idUsuario = $user['id_usuario'];

try {
    $fechaHoy = date('Y-m-d');

    // Verificar si el usuario actual tiene un cuadre bloqueado hoy
    $sql = "SELECT id, monto_recaudado, monto_entregado, bloqueado, fecha_registro
            FROM cuadres_asesores
            WHERE id_asesor = ? AND fecha_cuadre = ? AND bloqueado = 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$idUsuario, $fechaHoy]);
    $cuadre = $stmt->fetch();

    if ($cuadre) {
        echo json_encode([
            'success' => true,
            'bloqueado' => true,
            'mensaje' => 'Ya realizaste tu cuadre del día. No puedes hacer más cobros.',
            'cuadre' => [
                'monto_recaudado' => floatval($cuadre['monto_recaudado']),
                'monto_entregado' => floatval($cuadre['monto_entregado']),
                'fecha_cuadre' => $cuadre['fecha_registro']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'bloqueado' => false,
            'mensaje' => 'Puedes realizar cobros normalmente'
        ]);
    }

} catch (Exception $e) {
    error_log("Error verificando bloqueo de asesor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar estado del asesor'
    ]);
}
