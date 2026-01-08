<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

try {
    echo "Check column monto_pagado...\n";
    try {
        $db->query("SELECT monto_pagado FROM cuotas LIMIT 1");
        echo "Column 'monto_pagado' already exists.\n";
    } catch (Exception $e) {
        $db->exec("ALTER TABLE cuotas ADD COLUMN monto_pagado DECIMAL(10,2) DEFAULT 0.00 AFTER monto_cuota");
        echo "Column added.\n";
    }

    echo "Modifying 'estado' column to VARCHAR(20)...\n";
    $db->exec("ALTER TABLE cuotas MODIFY COLUMN estado VARCHAR(20) DEFAULT 'pendiente'");
    echo "Column 'estado' modified.\n";

    echo "Migration Success.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>