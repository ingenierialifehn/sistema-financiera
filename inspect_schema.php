<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$tables = ['bancos', 'agencias'];

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
