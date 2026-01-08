<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDB();
    $db->exec('DROP TABLE IF EXISTS transacciones_pagos');
    echo "✓ Tabla transacciones_pagos eliminada\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
