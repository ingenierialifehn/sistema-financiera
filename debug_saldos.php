<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM cajas_agencias");
    $cajas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Debug Cajas Agencias</h1>";
    echo "<pre>";
    print_r($cajas);
    echo "</pre>";

    echo "<h2>Sum check</h2>";
    $stmtSum = $db->query("SELECT SUM(saldo_caja_operativa) as total_cajas, SUM(saldo_efectivo) as total_bovedas FROM cajas_agencias");
    $sum = $stmtSum->fetch(PDO::FETCH_ASSOC);
    print_r($sum);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
