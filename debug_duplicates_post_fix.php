<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "=== MOVIMIENTOS INTERNOS AGENCIA UPDATED ===\n";
$stmt2 = $db->query("SELECT * FROM movimientos_internos_agencia WHERE DATE(fecha_movimiento) = CURDATE() AND tipo_movimiento = 'Recaudo Asesor'");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== CAJAS AGENCIAS UPDATED ===\n";
$stmt3 = $db->query("SELECT * FROM cajas_agencias");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
