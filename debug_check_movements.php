<?php
error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM movimientos_internos_agencia ORDER BY id_movimiento_interno DESC LIMIT 20");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
