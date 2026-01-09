<?php
/**
 * API para calcular el total recaudado según filtros
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();

$db = getDB();
$user = AuthMiddleware::getCurrentUser();
$userId = $user['id_usuario'];

// Obtener filtros
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$agenciaId = $_GET['agencia_id'] ?? null;
$cobradorId = $_GET['cobrador_id'] ?? null;

try {
    $sqlTotal = "SELECT IFNULL(SUM(c.monto_pagado), 0) 
                 FROM cuotas c 
                 JOIN prestamos p ON c.prestamo_id = p.id 
                 JOIN clientes cl ON p.id_cliente = cl.id 
                 WHERE DATE(c.fecha_pago_real) = ?";

    $params = [$fecha];

    // ============================================
    // MODO DESARROLLO: Sin restricciones de rol
    // ============================================

    // Filtro de agencia (opcional)
    if ($agenciaId) {
        $sqlTotal .= " AND cl.id_agencia = ?";
        $params[] = $agenciaId;
    }

    // Filtro de cobrador (opcional)
    if ($cobradorId) {
        $sqlTotal .= " AND (cl.cobrador_id = ? OR p.asesor_creditos_id = ? OR p.oficial_desembolsos_id = ?)";
        $params[] = $cobradorId;
        $params[] = $cobradorId;
        $params[] = $cobradorId;
    }

    $stmt = $db->prepare($sqlTotal);
    $stmt->execute($params);
    $totalRecaudado = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'total' => floatval($totalRecaudado)
    ]);

} catch (Exception $e) {
    error_log("Error calculando total recaudado: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al calcular total recaudado',
        'total' => 0
    ]);
}
