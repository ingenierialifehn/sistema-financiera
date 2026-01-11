<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT DISTINCT tipo_movimiento FROM movimientos_internos_agencia");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
