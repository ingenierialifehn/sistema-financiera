<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM prestamos WHERE Field = 'estado'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
