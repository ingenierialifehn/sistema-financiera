<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();

    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'observaciones'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE prestamos ADD COLUMN observaciones TEXT DEFAULT NULL");
        echo "Column 'observaciones' added successfully to 'prestamos'.\n";
    } else {
        echo "Column 'observaciones' already exists in 'prestamos'.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>