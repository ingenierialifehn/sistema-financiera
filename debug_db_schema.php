<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n";
print_r($tables);

if (in_array('plantillas_documentos', $tables)) {
    echo "\nColumns in plantillas_documentos:\n";
    $stmt = $db->query("DESCRIBE plantillas_documentos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if (in_array('configuracion', $tables)) {
    echo "\nColumns in configuracion:\n";
    $stmt = $db->query("DESCRIBE configuracion");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>