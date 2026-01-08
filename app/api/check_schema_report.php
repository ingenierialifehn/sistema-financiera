<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain');

try {
    $db = getDB();
    $tables = ['prestamos', 'cuotas', 'usuarios', 'pagos', 'movimientos_caja'];

    foreach ($tables as $table) {
        echo "\nTable: $table\n";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM $table");
            foreach ($stmt->fetchAll() as $col) {
                echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        } catch (Exception $e) {
            echo "  (Table not found or error: " . $e->getMessage() . ")\n";
        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
