<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE prestamos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if ($col['Field'] === 'estado') {
            echo "Field: " . $col['Field'] . "\n";
            echo "Type: " . $col['Type'] . "\n";
            echo "Null: " . $col['Null'] . "\n";
            break;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>