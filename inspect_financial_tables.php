<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$tables = ['movimientos_internos_agencia', 'bancos', 'movimientos_bancarios', 'cajas_agencias'];

foreach ($tables as $t) {
    echo "--- TABLE: $t ---\n";
    $stmt = $db->query("DESCRIBE $t");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo $c['Field'] . " | " . $c['Type'] . "\n";
    }
    echo "\n";
}
