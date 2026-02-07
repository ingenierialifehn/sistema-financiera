<?php
/**
 * API: Obtener lista de cuentas bancarias activas
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();

// Permitir si tiene permiso de bóveda, operaciones, tesorería o caja
if (
    !Auth::hasPermission('boveda') &&
    !Auth::hasPermission('operaciones.crear') &&
    !Auth::hasPermission('operaciones.editar') &&
    !Auth::hasPermission('tesoreria') &&
    !Auth::hasPermission('caja')
) {
    Response::forbidden('No tiene permiso para ver las cuentas bancarias');
}

$db = getDB();

try {
    $stmt = $db->query("
        SELECT 
            id,
            nombre_banco,
            numero_cuenta,
            tipo_cuenta,
            moneda,
            saldo_actual
        FROM bancos
        WHERE estado = 'activo'
        ORDER BY nombre_banco ASC
    ");
    $bancos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success([
        'bancos' => $bancos
    ]);

} catch (Exception $e) {
    error_log("Error en get_bancos.php: " . $e->getMessage());
    Response::serverError('Error al obtener cuentas bancarias');
}
