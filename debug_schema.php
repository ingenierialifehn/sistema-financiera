<?php
require_once 'config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE movimientos_internos_agencia");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
