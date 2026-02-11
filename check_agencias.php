<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
echo "=== COLUMNAS DE AGENCIAS ===\n";
$stmt = $db->query("DESCRIBE agencias");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>