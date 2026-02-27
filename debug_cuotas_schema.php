<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("DESCRIBE cuotas");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
