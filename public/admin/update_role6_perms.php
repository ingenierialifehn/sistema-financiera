<?php
require_once __DIR__ . '/../../app/config/database.php';
$db = getDB();

function addPermission($roleId, $module, $perms, $db)
{
    echo "Updating Role ID $roleId...\n";
    $stmt = $db->prepare("SELECT permisos FROM roles WHERE id_rol = :id");
    $stmt->execute(['id' => $roleId]);
    $row = $stmt->fetch();

    if (!$row) {
        echo "Role $roleId not found.\n";
        return;
    }

    $current = json_decode($row['permisos'], true) ?? [];

    if (!isset($current[$module])) {
        $current[$module] = $perms;
    } else {
        foreach ($perms as $k => $v) {
            $current[$module][$k] = $v;
        }
    }

    $newJson = json_encode($current);
    $update = $db->prepare("UPDATE roles SET permisos = :p WHERE id_rol = :id");
    $update->execute(['p' => $newJson, 'id' => $roleId]);
    echo "Role $roleId updated.\n";
}

// Fix Verificador de Microcreditos (Role 6)
addPermission(6, 'verificacion_campo', [
    'view' => true,
    'search' => true,
    'view_details' => true,
    'edit' => true, // Assuming they can verify
    'authorize' => true // Assuming they can authorize
], $db);

echo "Permissions for Role 6 updated successfully.\n";
