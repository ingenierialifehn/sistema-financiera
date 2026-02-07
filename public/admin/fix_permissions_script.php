<?php
// fix_permissions_script.php
// Script to patch permissions for Verification Module

require_once __DIR__ . '/../../app/config/database.php';

echo "Starting permission fix...\n";
$db = getDB();

// 1. Fix Administrator (Role 1)
try {
    $stmt = $db->prepare("UPDATE roles SET permisos = :p WHERE nombre_rol = 'Administrador'");
    $stmt->execute(['p' => json_encode(['todos' => true])]);
    echo "Fixed Administrator permissions.\n";
} catch (Exception $e) {
    echo "Error Administrator: " . $e->getMessage() . "\n";
}

// Helper to add permission
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

    // Merge or set
    if (!isset($current[$module])) {
        $current[$module] = $perms;
    } else {
        // Simple merge
        foreach ($perms as $k => $v) {
            $current[$module][$k] = $v;
        }
    }

    $newJson = json_encode($current);

    $update = $db->prepare("UPDATE roles SET permisos = :p WHERE id_rol = :id");
    $update->execute(['p' => $newJson, 'id' => $roleId]);
    echo "Role $roleId updated.\n";
}

// 2. Fix Asesor de Creditos (Role 4)
addPermission(4, 'verificacion_campo', [
    'view' => true,
    'search' => true,
    'view_details' => true
], $db);

// 3. Fix Oficial de Operaciones (Role 5)
addPermission(5, 'verificacion_campo', [
    'view' => true,
    'search' => true,
    'view_details' => true
], $db);

// 4. Fix Official de Desembolsos (Role 7) - Just in case
addPermission(7, 'verificacion_campo', [
    'view' => true,
    'search' => true,
    'view_details' => true
], $db);

// 5. Fix Analista de Creditos (Role 8) - Critical for next step
addPermission(8, 'verificacion_campo', [
    'view' => true,
    'search' => true,
    'view_details' => true
], $db);

echo "Permissions updated successfully.\n";
?>