<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

// Inspect Pagos table
echo "--- TABLA PAGOS ---\n";
try {
    $stmt = $db->query("DESCRIBE pagos");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo $c['Field'] . " (" . $c['Type'] . ")\n";
} catch (Exception $e) {
    echo "Tabla 'pagos' no encontrada o error: " . $e->getMessage();
}

// Si no es pagos, busquemos transacciones o cuotas_pagos
echo "\n--- TABLAS EN DB ---\n";
$stmt = $db->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>