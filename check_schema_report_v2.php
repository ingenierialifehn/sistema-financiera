<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$tables = ['movimientos_bancarios', 'movimientos_internos_agencia', 'cuotas', 'prestamos'];

foreach ($tables as $table) {
    echo "--- Structure of $table ---\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Table $table not found or error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "--- Distinct types in movimientos_internos_agencia ---\n";
try {
    $stmt = $db->query("SELECT DISTINCT tipo_movimiento FROM movimientos_internos_agencia");
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($types as $t) {
        echo "- $t\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Distinct types in movimientos_bancarios ---\n";
try {
    $stmt = $db->query("SELECT DISTINCT tipo_movimiento FROM movimientos_bancarios");
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($types as $t) {
        echo "- $t\n";
    }
} catch (Exception $e) {
    echo "Error (maybe table doesn't exist?): " . $e->getMessage() . "\n";
}
