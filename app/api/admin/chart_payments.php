<?php
/**
 * API: Datos para gráfica de pagos por día
 * GET /app/api/admin/chart_payments.php?days=30
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

    // Obtener número de días (default 30)
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $days = max(7, min(365, $days)); // Entre 7 y 365 días

    // Obtener datos de pagos
    $chartData = DashboardHelper::getPaymentsLastNDays($days);

    // Retornar respuesta exitosa
    Response::success($chartData, 'Datos de gráfica obtenidos exitosamente');

} catch (Exception $e) {
    error_log("Error en chart_payments.php: " . $e->getMessage());
    Response::serverError('Error al obtener datos de la gráfica');
}

