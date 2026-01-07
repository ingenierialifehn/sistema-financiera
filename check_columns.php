<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columnas en tabla 'clientes':\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
