<?php
/**
 * Listar Agencias
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requireAuth();
Auth::requirePermission('agencias');

$db = getDB();

try {
    $stmt = $db->query("
        SELECT a.id_agencia, a.nombre_agencia, a.direccion, a.ciudad, a.telefono_agencia, a.estado,
               COUNT(c.id_colaborador) as total_colaboradores
        FROM agencias a
        LEFT JOIN colaboradores c ON a.id_agencia = c.id_agencia AND c.estado_laboral != 'Despido'
        WHERE a.estado = 'Activa'
        GROUP BY a.id_agencia, a.nombre_agencia, a.direccion, a.ciudad, a.telefono_agencia, a.estado
        ORDER BY a.nombre_agencia ASC
    ");
    $agencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success($agencias);

} catch (Exception $e) {
    error_log("Error al listar agencias: " . $e->getMessage());
    Response::serverError('Error al cargar agencias');
}
