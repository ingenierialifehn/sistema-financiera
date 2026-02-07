<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT u.username, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE r.id_rol=6");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
