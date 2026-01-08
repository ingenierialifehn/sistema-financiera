<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("DESCRIBE prestamos");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
?>