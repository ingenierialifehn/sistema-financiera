<?php
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
