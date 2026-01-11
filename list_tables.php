<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $pdo = getDB();
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found:\n";
    foreach ($tables as $table) {
        echo "- " . $table . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
