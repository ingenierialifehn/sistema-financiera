<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain');

try {
    $db = getDB();
    echo "\n[Tabla Cuotas]\n";
    $stmt = $db->query("SHOW COLUMNS FROM cuotas");
    foreach ($stmt->fetchAll() as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>