<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$stmt = $db->query("SELECT id_rol, nombre_rol, permisos FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($roles as $role) {
    echo "ID: " . $role['id_rol'] . " - Name: " . $role['nombre_rol'] . "\n";
    echo "Permissions: " . $role['permisos'] . "\n\n";
}
