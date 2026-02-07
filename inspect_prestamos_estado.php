<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'estado'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($row);
?>