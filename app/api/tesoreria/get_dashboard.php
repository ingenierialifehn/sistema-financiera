<?php
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('tesoreria');

try {
    $db = getDB();

    // 1. Saldo Total Bancos (estado 'activo' - lowercase per setup_tesoreria)
    $stmt = $db->query("SELECT SUM(saldo_actual) as total FROM bancos WHERE estado = 'activo'");
    $saldoBancos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 2. Efectivo en Sucursales (Cajas + Bovedas)
    // Sumar saldos de la tabla cajas_agencias
    $stmtCajas = $db->query("SELECT SUM(saldo_caja_operativa) as total_cajas, SUM(saldo_efectivo) as total_bovedas FROM cajas_agencias");
    $resCajas = $stmtCajas->fetch(PDO::FETCH_ASSOC);

    $saldoCajas = $resCajas['total_cajas'] ?? 0;
    $saldoBovedas = $resCajas['total_bovedas'] ?? 0;

    // Debug
    error_log("Dashboard Tesoreria - Bancos: $saldoBancos, Cajas: $saldoCajas, Bovedas: $saldoBovedas");

    // 3. Patrimonio Available
    $patrimonio = $saldoBancos + $saldoCajas + $saldoBovedas;

    echo json_encode([
        'success' => true,
        'data' => [
            'saldo_bancos' => (float) $saldoBancos,
            'saldo_cajas' => (float) $saldoCajas,
            'saldo_bovedas' => (float) $saldoBovedas,
            'patrimonio' => (float) $patrimonio
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
