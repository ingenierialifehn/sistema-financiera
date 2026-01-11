<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$t = 'movimientos_internos_agencia';
echo "--- TABLE: $t ---\n";
$stmt = $db->query("DESCRIBE $t");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . "\n";
}
