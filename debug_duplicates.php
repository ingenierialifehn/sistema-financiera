<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

echo "=== CUADRES ASESORES HOY (" . date('Y-m-d') . ") ===\n";
$stmt = $db->query("SELECT * FROM cuadres_asesores WHERE fecha_cuadre = CURDATE()");
$cuadres = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cuadres);

echo "\n=== MOVIMIENTOS INTERNOS AGENCIA HOY ===\n";
$stmt2 = $db->query("SELECT * FROM movimientos_internos_agencia WHERE DATE(fecha_movimiento) = CURDATE() AND tipo_movimiento = 'Recaudo Asesor'");
$movs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($movs);

echo "\n=== CAJAS AGENCIAS ===\n";
$stmt3 = $db->query("SELECT * FROM cajas_agencias");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
