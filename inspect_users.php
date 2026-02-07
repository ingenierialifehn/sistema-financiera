<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

echo "--- ROLES ---\n";
$stmt = $db->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "ID: {$r['id_rol']} | Name: {$r['nombre_rol']} | Perms length: " . strlen($r['permisos']) . "\n";
}

echo "\n--- USERS ---\n";
$stmt = $db->query("SELECT id_usuario, username, id_rol, estado FROM usuarios");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id_usuario']} | User: {$row['username']} | RoleID: {$row['id_rol']} | Status: {$row['estado']}\n";
}
