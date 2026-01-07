<?php
/**
 * API: Resumen de métricas del dashboard
 * GET /app/api/admin/summary.php
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

    // Obtener resumen
    $summary = DashboardHelper::getSummary();

    // Retornar respuesta exitosa
    Response::success($summary, 'Resumen obtenido exitosamente');

} catch (Exception $e) {
    error_log("Error en summary.php: " . $e->getMessage());
    Response::serverError('Error al obtener el resumen');
}

