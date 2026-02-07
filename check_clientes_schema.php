<?php
require_once __DIR__ . '/app/config/database.php';
$db = getDB();

$stmt = $db->query("DESCRIBE clientes");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Estructura de la tabla Clientes:\n";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ") " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
