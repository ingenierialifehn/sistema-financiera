<?php
require_once '../../config/database.php';

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

Auth::requireAuth();
Auth::requirePermission('tesoreria');

try {
    $db = getDB();

    // 1. Saldo Total Bancos (estado 'activo' - lowercase per setup_tesoreria)
    $stmt = $db->query("SELECT SUM(saldo_actual) as total FROM bancos WHERE estado = 'activo'");
    $saldoBancos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 2. Efectivo en Bóveda/Cajas (Usuarios) (estado 'Activo' - Capitalized per check_tables)
    $stmt = $db->query("SELECT SUM(saldo_caja_virtual) as total FROM usuarios WHERE estado = 'Activo'");
    $saldoCajas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 3. Patrimonio Available
    $patrimonio = $saldoBancos + $saldoCajas;

    echo json_encode([
        'success' => true,
        'data' => [
            'saldo_bancos' => (float) $saldoBancos,
            'saldo_cajas' => (float) $saldoCajas,
            'patrimonio' => (float) $patrimonio
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
