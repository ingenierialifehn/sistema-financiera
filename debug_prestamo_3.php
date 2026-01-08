<?php
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, monto_capital, neto_entregar FROM prestamos WHERE id = 3");
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\n=== Datos del Préstamo #3 ===\n";
    echo "Monto Capital Actual (BD): " . $p['monto_capital'] . "\n";
    echo "Neto Entregado: " . $p['neto_entregar'] . "\n";

} catch (Exception $e) {
    echo $e->getMessage();
}
