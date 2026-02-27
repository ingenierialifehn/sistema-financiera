<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

try {
    // 1. Get Loan Details
    $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = 12");
    $stmt->execute();
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "=== PRESTAMO 12 ===\n";
    if ($prestamo) {
        foreach ($prestamo as $k => $v) {
            echo "$k: $v\n";
        }
    } else {
        echo "No detailed found for loan 12.\n";
    }

    // 2. Get Installments (Cuotas)
    $stmtCuotas = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id = 12 ORDER BY numero_cuota ASC");
    $stmtCuotas->execute();
    $cuotas = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== CUOTAS ===\n";
    foreach ($cuotas as $c) {
        echo "Cuota {$c['numero_cuota']}: Fecha: {$c['fecha_vencimiento']} | Capital: {$c['capital_cuota']} | Interes: {$c['interes_cuota']} | Estado: {$c['estado']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
