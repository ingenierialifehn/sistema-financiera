<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

echo "--- Prestamos 5 y 6 ---\n";
$stmt = $db->query("SELECT id, estado, monto_capital FROM prestamos WHERE id IN (5, 6)");
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($loans as $l) {
    echo "ID: {$l['id']} | Estado: {$l['estado']} | Monto: {$l['monto_capital']}\n";
}
