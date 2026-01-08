<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();
$stmt = $db->query('DESCRIBE usuarios');
echo "Columnas de la tabla usuarios:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>