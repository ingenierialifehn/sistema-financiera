<?php
require_once __DIR__ . '/../core/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("DESCRIBE colaboradores");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo " - {$col['Field']} ({$col['Type']})\n";
    }

} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage();
}
