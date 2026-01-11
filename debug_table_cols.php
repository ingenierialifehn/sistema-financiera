<?php
require_once 'config/database.php';
header('Content-Type: text/plain');

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE movimientos_internos_agencia");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
