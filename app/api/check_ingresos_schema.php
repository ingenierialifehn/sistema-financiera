<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    $target_tables = ['ingresos_bancos_agencia'];

    foreach ($target_tables as $table) {
        echo "\nTable: $table\n";
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo " - {$col['Field']} ({$col['Type']})\n";
            }
        } catch (Exception $e) {
            echo " - Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage();
}
