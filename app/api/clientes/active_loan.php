<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    // AuthMiddleware::requireAuth(); // Uncomment if auth is needed

    if (!isset($_GET['cliente_id'])) {
        throw new Exception("ID de cliente requerido");
    }

    $clienteId = intval($_GET['cliente_id']);
    $db = getDB();

    // Query to find active or overdue loan
    // We check for 'Activo' or 'Vencido' because both are valid for refinancing
    // We strictly look for the latest one.
    // Query to sum up outstanding capital of ALL active or overdue loans
    // This supports consolidation of multiple active loans.
    $stmt = $db->prepare("
        SELECT 
            SUM(p.monto_capital - (
                SELECT IFNULL(SUM(monto_pagado * (capital_cuota/monto_cuota)), 0) 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pagada', 'parcial') 
                AND monto_cuota > 0
            )) as saldo_total_capital
        FROM prestamos p
        WHERE p.id_cliente = ? 
        AND p.estado IN ('Activo', 'Vencido')
    ");

    $stmt->execute([$clienteId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['saldo_total_capital'] !== null && floatval($result['saldo_total_capital']) > 0) {
        $saldoCapital = floatval($result['saldo_total_capital']);

        echo json_encode([
            'success' => true,
            'has_active_loan' => true,
            'loan' => [
                'saldo_capital' => $saldoCapital,
                // We don't return specific loan details since it's an aggregate
                'estado' => 'Multiple'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_active_loan' => false
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>