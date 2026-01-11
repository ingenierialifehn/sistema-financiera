<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "--- Movimientos Agencia Nacaome ---\n";

// Find Nacaome ID
$stmt = $db->query("SELECT id_agencia, nombre_agencia FROM agencias WHERE nombre_agencia LIKE '%Nacaome%'");
$agencia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agencia) {
    die("Agencia Nacaome no encontrada.");
}

$id = $agencia['id_agencia'];
echo "Agencia: {$agencia['nombre_agencia']} (ID: $id)\n";

$sql = "SELECT id_movimiento_interno, tipo_movimiento, monto, fecha_movimiento, observaciones 
        FROM movimientos_internos_agencia 
        WHERE id_agencia = $id 
        ORDER BY id_movimiento_interno DESC";

$stmt = $db->query($sql);
$movs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "ID | Tipo | Monto | Fecha | Obs\n";
echo str_repeat("-", 80) . "\n";
foreach ($movs as $m) {
    echo "{$m['id_movimiento_interno']} | {$m['tipo_movimiento']} | {$m['monto']} | {$m['fecha_movimiento']} | {$m['observaciones']}\n";
}

// Check Balance
$stmtBal = $db->query("SELECT saldo_caja_operativa FROM cajas_agencias WHERE id_agencia = $id");
$bal = $stmtBal->fetchColumn();
echo "\nSaldo Actual en Caja: $bal\n";
