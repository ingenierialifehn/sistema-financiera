<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM prestamos LIKE 'neto_entregar'");
    if (!$stmt->fetch()) {
        // Add column
        $sql = "ALTER TABLE prestamos ADD COLUMN neto_entregar DECIMAL(10,2) NULL AFTER monto_capital";
        $db->exec($sql);

        // Update existing records to set neto_entregar = monto_capital
        $db->exec("UPDATE prestamos SET neto_entregar = monto_capital WHERE neto_entregar IS NULL");

        echo "Columna 'neto_entregar' agregada correctamente.";
    } else {
        echo "La columna 'neto_entregar' ya existe.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>