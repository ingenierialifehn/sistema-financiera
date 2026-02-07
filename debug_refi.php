<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "--- Schema: cuotas ---\n";
$stmt = $db->query("DESCRIBE cuotas");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($columns);

echo "\n--- Clients with Multiple Active/Vencido Loans ---\n";
$sql = "
    SELECT id_cliente, COUNT(*) as count, GROUP_CONCAT(id) as loan_ids 
    FROM prestamos 
    WHERE estado IN ('Activo', 'Vencido') 
    GROUP BY id_cliente 
    HAVING count > 0
    LIMIT 5
";
$stmt = $db->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $client) {
    echo "Client ID: " . $client['id_cliente'] . " | Count: " . $client['count'] . " | Loans: " . $client['loan_ids'] . "\n";

    // Test the specific calculation I used in active_loan.php
    $stmtCalc = $db->prepare("
        SELECT 
            SUM(p.monto_capital - (
                SELECT IFNULL(SUM(monto_pagado * (capital_cuota/NULLIF(monto_cuota,0))), 0) 
                FROM cuotas 
                WHERE prestamo_id = p.id 
                AND estado IN ('pagada', 'parcial') 
                AND monto_cuota > 0
            )) as saldo_calculado
        FROM prestamos p
        WHERE p.id_cliente = ? 
        AND p.estado IN ('Activo', 'Vencido')
    ");
    $stmtCalc->execute([$client['id_cliente']]);
    $res = $stmtCalc->fetch(PDO::FETCH_ASSOC);
    echo "  -> Calculated Consolidated Balance: " . $res['saldo_calculado'] . "\n";

    // Detailed breakdown per loan
    $loanIds = explode(',', $client['loan_ids']);
    foreach ($loanIds as $lid) {
        $stmtL = $db->prepare("SELECT monto_capital, monto_total FROM prestamos WHERE id = ?");
        $stmtL->execute([$lid]);
        $l = $stmtL->fetch(PDO::FETCH_ASSOC);

        $stmtC = $db->prepare("SELECT SUM(pagado_capital) as cap_paid FROM cuotas WHERE prestamo_id = ? AND estado IN ('pagada', 'parcial')");
        // Try selecting pagado_capital if it exists, otherwise we fallback to logic
        try {
            $stmtC->execute([$lid]);
            $c = $stmtC->fetch(PDO::FETCH_ASSOC);
            $paidCap = $c['cap_paid'];
            echo "     Loop Loan #$lid: Capital: {$l['monto_capital']} | Paid Cap (Direct): $paidCap\n";
        } catch (Exception $e) {
            echo "     Loop Loan #$lid: Capital: {$l['monto_capital']} | (pagado_capital column likely missing)\n";
        }
    }
}
?>