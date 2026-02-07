<?php
require_once 'app/config/database.php';
$db = getDB();
$stmt = $db->query("DESCRIBE control_caja_diaria");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
