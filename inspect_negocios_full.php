<?php
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM clientes_negocios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Field: " . $col['Field'] . " Type: " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
