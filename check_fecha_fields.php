<?php
require_once __DIR__ . '/app/config/database.php';

$db = getDB();

// Check prestamos table for date fields
echo "=== CAMPOS DE FECHA EN PRESTAMOS ===\n";
$stmt = $db->query("DESCRIBE prestamos");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (stripos($row['Field'], 'fecha') !== false || stripos($row['Field'], 'dia') !== false) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

// Check cuotas table
echo "\n=== CAMPOS EN CUOTAS ===\n";
$stmt2 = $db->query("DESCRIBE cuotas");
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Get sample data
echo "\n=== EJEMPLO DE PRIMERA CUOTA ===\n";
$stmt3 = $db->query("SELECT fecha_vencimiento, numero_cuota FROM cuotas WHERE id_prestamo = 1 ORDER BY numero_cuota LIMIT 1");
$cuota = $stmt3->fetch(PDO::FETCH_ASSOC);
if ($cuota) {
    print_r($cuota);
}
?>