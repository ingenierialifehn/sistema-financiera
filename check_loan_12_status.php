<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$loanId = 12;

$stmt = $db->prepare("SELECT id, numero_cuota, fecha_vencimiento, estado, capital_cuota, interes_cuota FROM cuotas WHERE prestamo_id = ? ORDER BY numero_cuota ASC, id ASC");
$stmt->execute([$loanId]);
$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cuotas as $c) {
    echo "Cuota {$c['numero_cuota']} (ID {$c['id']}): Date {$c['fecha_vencimiento']} | Status {$c['estado']} | Cap {$c['capital_cuota']}\n";
}
