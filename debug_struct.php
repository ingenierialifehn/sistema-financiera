<?php
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE cuotas");

    echo "=== Estructura Tabla CUOTAS ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
