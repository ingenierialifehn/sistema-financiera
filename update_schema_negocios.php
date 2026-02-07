<?php
require_once __DIR__ . '/app/config/database.php';
try {
    $db = getDB();
    // Add columns if they don't exist
    $columns = $db->query("SHOW COLUMNS FROM clientes_negocios")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('direccion_negocio', $columns)) {
        $db->exec("ALTER TABLE clientes_negocios ADD COLUMN direccion_negocio TEXT AFTER rubro");
        echo "Added direccion_negocio.\n";
    }

    if (!in_array('ingresos_promedio', $columns)) {
        $db->exec("ALTER TABLE clientes_negocios ADD COLUMN ingresos_promedio DECIMAL(10,2) DEFAULT 0 AFTER direccion_negocio");
        echo "Added ingresos_promedio.\n";
    }

    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
