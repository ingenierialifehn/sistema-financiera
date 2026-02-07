<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT u.username, r.nombre_rol, r.id_rol FROM usuarios u LEFT JOIN roles r ON u.id_rol = r.id_rol");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "User: " . $u['username'] . " - Role: " . $u['nombre_rol'] . " (ID: " . $u['id_rol'] . ")\n";
}
