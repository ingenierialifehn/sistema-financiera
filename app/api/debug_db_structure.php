<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain');

echo "=== ESTRUCTURA DB ===\n";

try {
    $db = getDB();

    // Tabla Prestamos
    echo "\n[Tabla Prestamos]\n";
    $stmt = $db->query("SHOW COLUMNS FROM prestamos");
    foreach ($stmt->fetchAll() as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    // Tabla Clientes
    echo "\n[Tabla Clientes]\n";
    $stmt = $db->query("SHOW COLUMNS FROM clientes");
    foreach ($stmt->fetchAll() as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>