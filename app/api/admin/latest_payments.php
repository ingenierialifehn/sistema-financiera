<?php
/**
 * API: Últimos pagos registrados
 * GET /app/api/admin/latest_payments.php?limit=20
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../core/DashboardHelper.php';
require_once __DIR__ . '/../../core/Response.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

try {
    // Requerir autenticación
    AuthMiddleware::requireAuth();
    // Requerir permiso de dashboard
    Auth::requirePermission('dashboard');
    $user = Auth::getCurrentUser();

    // Obtener límite (default 20)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $limit = max(1, min(100, $limit)); // Entre 1 y 100

    // Obtener últimos pagos
    $payments = DashboardHelper::getLatestPayments($limit);

    // Retornar respuesta exitosa
    Response::success([
        'payments' => $payments,
        'total' => count($payments)
    ], 'Últimos pagos obtenidos exitosamente');

} catch (Exception $e) {
    error_log("Error en latest_payments.php: " . $e->getMessage());
    Response::serverError('Error al obtener últimos pagos');
}

