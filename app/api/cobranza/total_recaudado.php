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

    // Modificación Solicitada: 
    // "sumale todo lo cobrado hoy en la tabla cuotas, a los valores que tengan el usuario_cobro_id y el usuario que inicio secion"

    // Filtramos directamente por quien COBRÓ el dinero (usuario_cobro_id)
    // Ya no importa de qué agencia es el cliente o quién era el asesor original, importa quién ejecutó el cobro.

    $cobradorRealId = ($userId && $userId > 0) ? $userId : -1;

    $sqlTotal .= " AND c.usuario_cobro_id = ?";
    $params[] = $cobradorRealId;

    // Nota: El filtro de agencia ($agenciaId) anterior filtraba por la agencia DEL CLIENTE (cl.id_agencia).
    // Si queremos ver lo que YO cobré, independientemente de la agencia del cliente, deberíamos comentar el filtro de agencia 
    // o asegurarnos de que solo cobramos en nuestra agencia. 
    // Por seguridad y consistencia con la solicitud "lo que tenga el usuario_cobro_id y el usuario que inicio sesion",
    // el filtro dominante es el ID de usuario.

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
