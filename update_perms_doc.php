<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();

    // Grant 'documentacion' permissions to Admin (1) and Gerente (2)
    $stmt = $db->prepare("SELECT permisos FROM roles WHERE id_rol IN (1, 2)");
    $stmt->execute();

    $roles = $db->query("SELECT id_rol, permisos FROM roles WHERE id_rol IN (1, 2)")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($roles as $role) {
        $perms = json_decode($role['permisos'], true) ?? [];

        // Add new permissions
        $perms['documentacion'] = [
            'view' => true,
            'reprint' => true,
            'edit_templates' => true,
            'config_logo' => true
        ];

        $newJson = json_encode($perms);

        $update = $db->prepare("UPDATE roles SET permisos = ? WHERE id_rol = ?");
        $update->execute([$newJson, $role['id_rol']]);

        echo "Updated permissions for Role ID " . $role['id_rol'] . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>