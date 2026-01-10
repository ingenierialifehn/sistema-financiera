<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);
