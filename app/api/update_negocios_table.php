<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Add columns for guarantee photos if they don't exist
    $columns = ['foto_garantia_1', 'foto_garantia_2', 'foto_garantia_3'];

    foreach ($columns as $col) {
        $stmt = $db->query("SHOW COLUMNS FROM clientes_negocios LIKE '$col'");
        if (!$stmt->fetch()) {
            $sql = "ALTER TABLE clientes_negocios ADD COLUMN $col VARCHAR(255) AFTER garantia_valor";
            $db->exec($sql);
            echo "Columna '$col' agregada.\n";
        } else {
            echo "Columna '$col' ya existe.\n";
        }
    }

    echo "Migración de garantías completada.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
