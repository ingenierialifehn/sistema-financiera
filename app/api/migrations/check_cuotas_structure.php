<?php
require_once __DIR__ . '/../../config/database.php';

$db = getDB();
$stmt = $db->query('DESCRIBE cuotas');
echo "Estructura de la tabla cuotas:\n\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
