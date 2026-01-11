<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check tables
    $tables = ['usuarios', 'colaboradores'];

    foreach ($tables as $table) {
        echo "Table: $table\n";
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo " - {$col['Field']} ({$col['Type']})\n";
            }
        } catch (Exception $e) {
            echo " - Table not found or error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage();
}
