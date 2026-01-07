<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'clientes_negocios'");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($table) {
        echo "Tabla 'clientes_negocios' EXISTE.\n";
        $stmt = $db->query("DESCRIBE clientes_negocios");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . "\n";
        }
    } else {
        echo "Tabla 'clientes_negocios' NO EXISTE.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
